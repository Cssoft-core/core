<?php
namespace CSSoft\Core\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use CSSoft\Core\Model\ComponentList\Loader;

/**
 * Command for displaying status of cssoft modules
 */
class ModuleListCommand extends Command
{
    const INPUT_OPTION_TYPE = 'type';
    const INPUT_OPTION_ALL = 'all';
    const INPUT_OPTION_ALL_SHORTCUT = 'a';
    const INPUT_OPTION_INSTALLED = 'installed';
    const INPUT_OPTION_OUTDATED = 'outdated';

    /**
     *
     * @var \CSSoft\Core\Model\ComponentList\Loader
     */
    private $loader;

    /**
     * Inject dependencies
     *
     * @param \CSSoft\Core\Model\ComponentList\Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            self::INPUT_OPTION_TYPE,
            null,
            InputOption::VALUE_OPTIONAL,
            'module-type [module|theme|magento2-module|magento2-theme].',
            ''
        );

        $this->addOption(
            self::INPUT_OPTION_ALL,
            self::INPUT_OPTION_ALL_SHORTCUT,
            InputOption::VALUE_NONE,
            'Show all information'
        );

        $this->addOption(
            self::INPUT_OPTION_INSTALLED,
            'i',
            InputOption::VALUE_NONE,
            'Show only installed'
        );

        $this->addOption(
            self::INPUT_OPTION_OUTDATED,
            'o',
            InputOption::VALUE_NONE,
            'Show only outdated'
        );

        $this->setName('cssoft:module:list')
            ->setDescription('Displays status of cssoft modules');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::INPUT_OPTION_TYPE);
        $all = (bool) $input->getOption(self::INPUT_OPTION_ALL);
        $showInstalled = (bool) $input->getOption(self::INPUT_OPTION_INSTALLED);
        $showOutdated = (bool) $input->getOption(self::INPUT_OPTION_OUTDATED);

        if (!in_array($type, ['magento2-module', 'magento2-theme'])) {
            $type = 'magento2-' . $type;
        }
        if (!in_array($type, ['magento2-module', 'magento2-theme'])) {
            $type = false;
        }

        $items = $this->loader->getItems();
        $output->writeln('<info>List of cssoft modules</info> : ' . count($items));

        $rows = [];
        $i = 0;
        $separator = new TableSeparator();
        $columns = explode(',', 'name,code,version,latest_version,type');
        if ($all) {
            $columns[] = 'release_date';
            $columns[] = 'path';
        }
        foreach ($items as $item) {
            if ($type !== false && $item['type'] != $type) {
                continue;
            }
            $row = [];
            foreach ($columns as $key) {
                $row[$key] = isset($item[$key]) ? $item[$key]: '';
            }

            if ($showInstalled && empty($row['version'])) {
                continue;
            }

            $isOutdated = version_compare($row['version'], $row['latest_version'], '<');
            if ($showOutdated && !$isOutdated) {
                continue;
            }

            $color = !$isOutdated ? 'green' : 'red';
            $row['version'] = "<fg={$color}>{$row['version']}</>";

            if (isset($row['release_date'])) {
                $row['release_date'] = date("Y-m-d", strtotime($row['release_date']));
            }
            if (!empty($row['path']) && strstr($row['path'], '/vendor/')) {
                list(, $row['path']) = explode('/vendor/', $row['path']);
                $row['path'] = './vendor/' . $row['path'];
            }

            $rows[] = $row;

            $i++;
            if ($i === 10) {
                $i = 0;
                $rows[] = $separator;
            }
        }

        $table = new Table($output);
        $headers = ['Package', 'Module', 'Version', 'Latest', 'Type'];
        if ($all) {
            $headers[] = 'Date';
            $headers[] = 'Path';
        }
        $table->setHeaders($headers);
        $table->setRows($rows);

        $table->render();

        return Cli::RETURN_SUCCESS;
    }
}
