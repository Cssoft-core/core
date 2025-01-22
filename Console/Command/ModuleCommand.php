<?php
namespace CSSoft\Core\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use CSSoft\Core\Model\ComponentList\Loader;

/**
 * Command for displaying info of cssoft module
 */
class ModuleCommand extends Command
{
    const INPUT_ARGUMENT_NAME = 'name';

    /**
     *
     * @var \CSSoft\Core\Model\ComponentList\Loader
     */
    private $loader;

    /**
     *
     * @var \CSSoft\Core\Model\ModuleFactory
     */
    private $moduleFactory;

    /**
     * Inject dependencies
     *
     * @param \CSSoft\Core\Model\ComponentList\Loader $loader
     * @param \CSSoft\Core\Model\ModuleFactory $moduleFactory
     */
    public function __construct(Loader $loader, \CSSoft\Core\Model\ModuleFactory $moduleFactory)
    {
        $this->loader = $loader;
        $this->moduleFactory = $moduleFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_ARGUMENT_NAME,
            InputArgument::OPTIONAL,
            'Package name or Module name (cssoft/core or CSSoft_Core)',
            false
        );

        $this->setName('cssoft:module')
            ->setDescription('Displays info of cssoft module');
        parent::configure();
    }

    private function getLabelMapping()
    {
        return [
            'name' => 'Package',
            'code' => 'Module',
            'type' => 'Type',
            'description' => 'Description',
            'keywords' => 'Keywords',
            'version' => 'Version',
            'latest_version' => 'Latest Version',
            'release_date' => 'Released Date',
            'path' => 'Path',
            'link' => 'Homepage',
            'docs_link' => 'Documentation',
            // 'download_link' => 'Download',
            'changelog_link' => 'Changelog',
            // 'identity_key_link'
        ];
    }

     /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleCode = $input->getArgument(self::INPUT_ARGUMENT_NAME);
        if (empty($moduleCode)) {
            $command = $this->getApplication()->find('cssoft:module:list');

            $arguments = array(
                'command' => 'cssoft:module:list',
            );

            $greetInput = new ArrayInput($arguments);
            return $command->run($greetInput, $output);
        }

        $items = $this->loader->getItems();

        $codes = array_column($items, 'code', 'name');
        $packages = array_keys($codes);
        if (in_array('CSSoft_' . $moduleCode, $codes)) {
            $moduleCode = 'CSSoft_' . $moduleCode;
        } elseif (in_array('CSSoft_' . ucfirst($moduleCode), $codes)) {
            $moduleCode = 'CSSoft_' . ucfirst($moduleCode);
        } elseif (in_array('cssoft/' . $moduleCode, $packages)) {
            $moduleCode = 'cssoft/' . $moduleCode;
        } elseif (in_array('cssoft/module-' . $moduleCode, $packages)) {
            $moduleCode = 'cssoft/module-' . $moduleCode;
        }

        if (in_array($moduleCode, $packages)) {
            $moduleCode = $codes[$moduleCode];
        }
        // $output->writeln($moduleName);
        if (!isset($items[$moduleCode])) {
            $output->writeln('<error>Package[Module] ' . $moduleCode .' doesn\'t exist</error>');
            $output->writeln('Run : <fg=yellow>php bin/magento cssoft:module:list</>');
            return Cli::RETURN_FAILURE;
        }
        $item = $items[$moduleCode];

        if (!isset($item['version'])) {
            $item['version'] = '';
        }

        if (!isset($item['latest_version'])) {
            $item['latest_version'] = '';
        }

        $color = version_compare($item['version'], $item['latest_version'], '>=') ? 'green' : 'red';
        $item['version'] = "<fg={$color}>{$item['version']}</>";
        $item['release_date'] = date(
            "Y-m-d H:i",
            isset($item['release_date']) ? strtotime($item['release_date']) : time()
        );

        $rows = [];
        foreach ($this->getLabelMapping() as $key => $label) {
            if (isset($item[$key])) {
                // $output->writeln("<info>$label : </info>" . $item[$key]);
                $rows[] = ["<info>$label</info>", $item[$key]];
            }
        }

        $moduleModel = $this->moduleFactory->create();
        $moduleModel->load($moduleCode);

        $identityKey = $moduleModel->getData('identity_key');
        if (!empty($identityKey)) {
            $rows[] = ["<info>Identity Key</info>", $identityKey];
        }

        $depends = $moduleModel->getData('depends');
        if (!empty($depends)) {
            $rows[] = ["<info>Depends</info>", implode(' ', $depends)];
        }

        $table = new Table($output);
        // $table->setHeaders(['Param', 'Value']);
        $table->setRows($rows);
        $table->render();

        return Cli::RETURN_SUCCESS;
    }
}
