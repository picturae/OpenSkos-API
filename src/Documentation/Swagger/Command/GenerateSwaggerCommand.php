<?php

namespace App\Documentation\Swagger\Command;

use App\Annotation\OA;
use Doctrine\Common\Annotations\AnnotationReader;
use Gnugat\NomoSpaco\File\FileRepository;
use Gnugat\NomoSpaco\FqcnRepository;
use Gnugat\NomoSpaco\Token\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class GenerateSwaggerCommand extends Command
{
    protected static $defaultName = 'swagger:generate';

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var string
     */
    protected $swaggerfile;

    /**
     * @var object
     */
    protected $composer;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $servers;

    public function __construct(
        ParameterBagInterface $parameterBag,
        string $swaggerfile,
        string $title,
        string $description,
        array $servers
    ) {
        $this->params      = $parameterBag;
        $this->swaggerfile = $_SERVER['APP_ROOT'].$swaggerfile;
        $this->composer    = json_decode(file_get_contents($_SERVER['APP_ROOT'].'/composer.json'));
        $this->title       = $title;
        $this->description = $description;
        $this->servers     = $servers;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate swagger documentation file')
            ->setHelp('Generates the swagger documentation file for the /docs endpoint')
        ;
    }

    protected function array2yaml(array $subject, int $indent = 0, int $skipIndent = 0): ?string
    {
        $output = '';
        foreach ($subject as $key => $value) {
            if (is_int($key)) {
                $key = '-';
            }
            if (is_string($value)) {
                if ('-' === $key) {
                    $output .= str_repeat($skipIndent ? '' : '  ', $indent).'- "'.str_replace('"', '\\"', $value)."\"\n";
                } else {
                    $output .= str_repeat($skipIndent ? '' : '  ', $indent)."\"${key}\": \"".str_replace('"', '\\"', $value)."\"\n";
                }
            }
            if (is_object($value)) {
                if (method_exists($value, '__toArray')) {
                    $value = $value->__toArray();
                } else {
                    $value = get_object_vars($value);
                }
            }
            if (is_array($value)) {
                if (!count($value)) {
                    continue;
                }
                if ('-' === $key) {
                    $output .= str_repeat($skipIndent ? '' : '  ', $indent).$key.' ';
                    $output .= $this->array2yaml($value, $indent + 1, 1) ?? '';
                } else {
                    $output .= str_repeat($skipIndent ? '' : '  ', $indent)."\"{$key}\":\n";
                    $output .= $this->array2yaml($value, $indent + 1) ?? '';
                }
            }
            $skipIndent = 0;
        }

        return $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start file writer with header
        $fd = fopen($this->swaggerfile, 'w');
        fwrite($fd, sprintf("openapi: 3.0.0\n"));
        fwrite($fd, sprintf("info:\n"));
        fwrite($fd, sprintf("  title      : \"%s\"\n", $this->title));
        fwrite($fd, sprintf("  description: \"%s\"\n", $this->description));
        fwrite($fd, sprintf("  version    : \"%s\"\n", $this->composer->version));
        fwrite($fd, sprintf("\n"));

        // Write servers from given params
        fwrite($fd, sprintf("servers:\n"));
        fwrite($fd, $this->array2yaml($this->servers, 1) ?? "\n");
        fwrite($fd, sprintf("\n"));

        // Write start of the path list
        fwrite($fd, sprintf("paths:\n"));

        // Fetch all classes in our app
        $fileRepository = new FileRepository();
        $parserFactory  = new ParserFactory();
        $fqcnRepository = new FqcnRepository($fileRepository, $parserFactory);
        $classes        = $fqcnRepository->findIn($_SERVER['APP_ROOT'].'/src');
        $knownMethods   = [];

        // Prepare the annotation reader
        $annotationReader = new AnnotationReader();

        /* // All error annotations will be store here */
        /* $errorAnnotations = []; */
        $endpoints = [];

        // Loop through classes
        foreach ($classes as $class) {
            try {
                $reflectionClass = new \ReflectionClass($class);
            } catch (\Exception $e) {
                continue;
            }

            // TODO: read class annotations to detect params

            // Fetch and loop through methods
            $methods = $reflectionClass->getMethods();
            foreach ($methods as $reflectionMethod) {
                // Prevent inheritance from double-throwing a method
                $methodFullName = $reflectionMethod->class.'->'.$reflectionMethod->name;
                if (isset($knownMethods[$methodFullName])) {
                    continue;
                } else {
                    $knownMethods[$methodFullName] = true;
                }

                // Fetch the method's anotations
                try {
                    $annotations = $annotationReader->getMethodAnnotations($reflectionMethod);
                } catch (\Exception $e) {
                    continue;
                }

                // No annotations = not interesting
                if (!count($annotations)) {
                    continue;
                }

                $path             = '';
                $annotatedMethods = [];
                $data             = new \stdClass();
                $data->summary    = '';

                foreach ($annotations as $annotation) {
                    // Route annotation
                    if ($annotation instanceof Route) {
                        $path             = $annotation->getPath();
                        $annotatedMethods = $annotation->getMethods();
                        $path             = str_replace('?}', '}', $path);
                    }

                    if ($annotation instanceof OA\Description) {
                        $data->description = $annotation->value;
                    }
                    if ($annotation instanceof OA\Summary) {
                        $data->summary = $annotation->value;
                    }

                    if ($annotation instanceof OA\Response) {
                        $annotationData = $annotation->__toArray();
                        $code           = $annotationData['code'];
                        $fields         = $annotationData['fields'] ?? [];
                        unset($annotationData['fields']);
                        unset($annotationData['code']);
                        $data->responses            = array_merge_recursive(
                            $data->responses ?? [],
                            ["'$code'"=> ['content' => $annotationData['content']->__toArray()]],
                        );
                    }

                    if ($annotation instanceof OA\Request) {
                        $data->parameters = $annotation->parameters;
                    }
                }

                if (!count($annotatedMethods)) {
                    continue;
                }

                foreach ($annotatedMethods as $method) {
                    $endpoints[$path][$method] = (array) $data;
                }
            }
        }

        foreach ($endpoints as $path => $pathMethods) {
            fwrite($fd, sprintf("  %s:\n", $path));
            foreach ($pathMethods as $method => $descriptor) {
                fwrite($fd, sprintf("    %s:\n", strtolower($method)));
                fwrite($fd, $this->array2yaml($descriptor, 3) ?? '');
            }
        }

        fclose($fd);
    }
}
