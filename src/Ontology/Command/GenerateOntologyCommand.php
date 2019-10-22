<?php

namespace App\Ontology\Command;

use App\Ontology\Template\Template;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GenerateOntologyCommand extends Command
{
    protected static $defaultName = 'ontology:generate';

    /** @var string */
    protected static $ontologyNamespace = 'App\\Ontology\\';

    /** @var string */
    protected static $ontologaDirectory = __DIR__.'/../../Ontology';

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
        $dir = dirname(__DIR__);
        $ds = DIRECTORY_SEPARATOR;
        $context = $this->params->get('ontology');

        // Step 1: build context file
        file_put_contents("${dir}${ds}Context.php", Template::render('Context', [
            'context' => $context,
        ]));

        // Build ontology file for referenced vocabulary
        foreach ($context as $ontology) {
            if (!array_key_exists('properties', $ontology)) {
                continue;
            }

            $name = $ontology['name'];

            // Normalize property descriptors
            $properties = array_map(function ($propertyDescriptor) {
                if (is_string($propertyDescriptor)) {
                    return [
                        'name' => $propertyDescriptor,
                        'const' => strtoupper(Template::from_camel_case($propertyDescriptor)),
                    ];
                }

                return null;
            }, $ontology['properties']);

            file_put_contents("${dir}${ds}${name}.php", Template::render('Namespace', [
                'name' => $ontology['name'],
                'namespace' => $ontology['namespace'],
                'properties' => $properties,
            ]));
        }

        /* // Fetch names to build */
        /* $generateNames = json_decode(file_get_contents(implode(DIRECTORY_SEPARATOR, [ */
        /*     __DIR__, */
        /*     '..', */
        /*     'ontology.json' */
        /* ]))); */

        /* foreach( $generateNames as $className ) { */

        /* } */

        /* var_dump($generateNames); */
        /* var_dump($container); */

        /* $output->writeln([ */
        /*     'Hello', */
        /*     'world' */
        /* ]); */
    }
}
