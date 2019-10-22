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
        file_put_contents("${dir}${ds}Context.php", "<?php\n\n".(Template::render('Context', [
            'context' => $context,
        ])));

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
