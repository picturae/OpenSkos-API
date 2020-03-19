<?php

namespace App\Ontology\Command;

use App\Template\Template;
use PhpCsFixer\Config;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\Error\ErrorsManager;
use PhpCsFixer\Runner\Runner;
use PhpCsFixer\ToolInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GenerateOntologyCommand extends Command
{
    protected static $defaultName = 'ontology:generate';

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
            ->setDescription('Generate ontology files')
            ->setHelp('This command generates ontology files for openskos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir     = dirname(__DIR__);
        $ds      = DIRECTORY_SEPARATOR;
        $context = $this->params->get('ontology');

        // Build datatype list
        $datatype = [];
        foreach ($context as $ontology) {
            $ontology['properties'] = $ontology['properties'] ?? [];
            foreach ($ontology['properties'] as $key => $propertyDescriptor) {
                if (is_string($propertyDescriptor)) {
                    $datatype[$ontology['prefix'].':'.$propertyDescriptor] = 'literal';
                } elseif (is_array($propertyDescriptor)) {
                    $propertyDescriptor['datatype']         = $propertyDescriptor['datatype'] ?? 'literal';
                    $datatype[$ontology['prefix'].':'.$key] = $propertyDescriptor['datatype'];
                }
            }
        }

        // Build dataclass list
        $dataclass = [];
        foreach ($context as $ontology) {
            $ontology['properties'] = $ontology['properties'] ?? [];
            foreach ($ontology['properties'] as $key => $propertyDescriptor) {
                if (is_string($propertyDescriptor)) {
                    continue;
                }
                if (!is_array($propertyDescriptor)) {
                    continue;
                }
                if (!isset($propertyDescriptor['dataclass'])) {
                    continue;
                }
                $dataclass[$ontology['prefix'].':'.$key] = $propertyDescriptor['dataclass'];
            }
        }

        // Step 1: build context file
        file_put_contents("${dir}${ds}Context.php", Template::render('Ontology/Context', [
            'context'   => $context,
            'dataclass' => $dataclass,
            'datatype'  => $datatype,
        ]));

        // Build ontology file for referenced vocabulary
        foreach ($context as $ontology) {
            $name = $ontology['name'];

            // Normalize property descriptors
            $properties                = [];
            $vocabulary                = [];
            $ontology['hasVocabulary'] = $ontology['hasVocabulary'] ?? false;
            foreach ($ontology['properties'] as $key => $propertyDescriptor) {
                // Handle string property descriptor
                if (is_string($propertyDescriptor)) {
                    $properties[] = [
                        'name'          => $propertyDescriptor,
                        'const'         => strtoupper(Template::from_camel_case($propertyDescriptor)),
                        'hasValidation' => false,
                        'regex'         => null,
                        'datatype'      => 'literal',
                        'literaltype'   => null,
                        'enum'          => null,
                    ];
                    continue;
                }

                // Handle more complex property
                if (is_array($propertyDescriptor)) {
                    $hasValidation = isset($propertyDescriptor['regex']) ||
                        isset($propertyDescriptor['literaltype'])        ||
                        isset($propertyDescriptor['enum'])               ||
                    0;

                    // Enum list reference
                    if (isset($propertyDescriptor['enum']) &&
                        is_string($propertyDescriptor['enum']) &&
                        '!' == substr($propertyDescriptor['enum'], 0, 1)
                    ) {
                        $listName                   = substr($propertyDescriptor['enum'], 1);
                        $propertyDescriptor['enum'] = $ontology[$listName];
                    }

                    $properties[] = [
                        'name'          => $key,
                        'const'         => strtoupper($propertyDescriptor['const'] ?? Template::from_camel_case($key)),
                        'hasValidation' => $hasValidation,
                        'regex'         => $propertyDescriptor['regex'] ?? null,
                        'datatype'      => $propertyDescriptor['datatype'] ?? 'literal',
                        'literaltype'   => $propertyDescriptor['literaltype'] ?? null,
                        'enum'          => $propertyDescriptor['enum'] ?? null,
                    ];
                    if ($ontology['hasVocabulary']) {
                        $propertyDescriptor['name'] = $key;
                        $vocabulary[]               = $propertyDescriptor;
                    }
                    continue;
                }
            }

            // Custom consts
            $consts            = [];
            $lists             = [];
            $ontology['const'] = $ontology['const'] ?? [];
            foreach ($ontology['const'] as $constName => $constDescriptor) {
                $constName = strtoupper($constName);

                // Handle '@source'
                if (is_string($constDescriptor) &&
                    '!' == substr($constDescriptor, 0, 1)
                ) {
                    $constDescriptor = [
                        'type'   => 'list',
                        'source' => substr($constDescriptor, 1),
                    ];
                }

                switch ($constDescriptor['type']) {
                    case 'list':
                        foreach ($ontology[$constDescriptor['source']] as $constValue) {
                            $upper               = strtoupper($constDescriptor['source'].'_'.$constValue);
                            $consts[$upper]      = $constValue;
                            $lists[$constName][] = 'self::'.$upper;
                        }
                        break;
                }
            }

            // Custom lists
            $ontology['list'] = $ontology['list'] ?? [];
            foreach ($ontology['list'] as $listName => $listDescriptor) {
                $listName = strtoupper($listName);
                foreach ($listDescriptor as $listValue) {
                    $upper              = strtoupper($listValue);
                    $lists[$listName][] = 'self::'.$upper;
                }
            }

            file_put_contents("${dir}${ds}${name}.php", Template::render('Ontology/Namespace', [
                'context'    => $context,
                'name'       => $ontology['name'],
                'prefix'     => $ontology['prefix'],
                'namespace'  => $ontology['namespace'],
                'properties' => $properties,
                'vocabulary' => $vocabulary,
                'consts'     => $consts,
                'lists'      => $lists,
                'dataclass'  => $dataclass,
                'datatype'   => $datatype,
            ]));
        }

        $config   = require "${dir}/../../.php_cs";
        $resolver = new ConfigurationResolver(
            new Config(),
            [
                'allow-risky'       => 'yes',
                'config'            => '.php_cs',
                'dry-run'           => false,
                'rules'             => json_encode($config->getRules()),
                'path'              => glob('src/Ontology/*.php'),
                'path-mode'         => 'override',
                'using-cache'       => false,
                'cache-file'        => false,
                'format'            => false,
                'diff'              => false,
                'diff-format'       => false,
                'stop-on-violation' => false,
                'verbosity'         => false,
                'show-progress'     => false,
            ],
            "${dir}/../..",
            new ToolInfo()
        );
        $runner   = new Runner(
            $resolver->getFinder(),
            $resolver->getFixers(),
            $resolver->getDiffer(),
            null,
            new ErrorsManager(),
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation()
        );

        $runner->fix();
    }
}
