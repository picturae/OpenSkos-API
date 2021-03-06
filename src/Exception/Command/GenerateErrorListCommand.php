<?php

namespace App\Exception\Command;

use App\Annotation\Error;
use App\Template\Template;
use Doctrine\Common\Annotations\AnnotationReader;
use Gnugat\NomoSpaco\File\FileRepository;
use Gnugat\NomoSpaco\FqcnRepository;
use Gnugat\NomoSpaco\Token\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GenerateErrorListCommand extends Command
{
    protected static $defaultName = 'exception:errorlist';

    /** @var ParameterBagInterface */
    protected $params;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->params = $parameterBag;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate error list')
            ->setHelp('Searches in all project files for error annotations and compiles a list based on them')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = dirname(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        // Fetch all classes in our app
        $fileRepository = new FileRepository();
        $parserFactory  = new ParserFactory();
        $fqcnRepository = new FqcnRepository($fileRepository, $parserFactory);
        $classes        = $fqcnRepository->findIn($_SERVER['APP_ROOT'].'/src');

        // Prepare the annotation reader
        $annotationReader = new AnnotationReader();

        // All error annotations will be store here
        $errorAnnotations = [];

        $knownMethods = [];
        $knownErrors  = [];

        // Loop through classes
        foreach ($classes as $class) {
            try {
                $reflectionClass = new \ReflectionClass($class);
            } catch (\Exception $e) {
                continue;
            }

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

                // Only keep Error annotations
                $annotations = array_filter($annotations, function ($annotation) {
                    return $annotation instanceof Error;
                });

                // No annotations = not interesting
                if (!count($annotations)) {
                    continue;
                }

                foreach ($annotations as $annotation) {
                    $annotationData = $annotation->__toArray();
                    if (empty($annotationData['code'])) {
                        $annotationData['code'] = $annotationData['value'] ?? '';
                        unset($annotationData['value']);
                    }

                    if (isset($knownErrors[$annotationData['code']])) {
                        $code   = $annotationData['code'];
                        $origin = $knownErrors[$annotationData['code']];
                        throw new \Exception("Duplicate error '${code}' in $methodFullName, first defined for $origin");
                    } else {
                        $knownErrors[$annotationData['code']] = $methodFullName;
                    }

                    array_push($errorAnnotations, array_merge(
                        $annotationData,
                        [
                            'class'  => $reflectionMethod->class,
                            'method' => $reflectionMethod->name,
                        ]
                    ));
                }
            }
        }

        // Render the new Error annotation
        file_put_contents("${dir}/../Exception/list.json", Template::render('Exception/list.json', [
            'usages' => $errorAnnotations,
        ]));
    }
}
