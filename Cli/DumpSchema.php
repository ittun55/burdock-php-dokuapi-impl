<?php
namespace Api\Cli;

use Burdock\DataModel\Migrator;
use Burdock\DokuApi\Container;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class DumpSchema extends Command
{
    /**
     * @var string コマンド名
     */
    protected static $defaultName = 'dump:schema';

    /**
     * SampleCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 引数、オプションの設定
     */
    protected function configure()
    {
        $this->setDescription('Database Migration Tools');

        $msg = 'This command dumps db schema to json.' . PHP_EOL;
        $msg.= 'Also creates model classes.' . PHP_EOL;
        $this->setHelp($msg);

        $this->addOption(
            'config', 'c',InputOption::VALUE_REQUIRED,
            'path to config file.'
        );

        $this->addOption(
            'connection', 'o',InputOption::VALUE_REQUIRED,
            'db connection name.'
        );

        $this->addOption(
            'rehearsal', 'r',InputOption::VALUE_NONE,
            'not save. just showing the result.'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        try {
            $config_path = $input->getOption('config');
            Container::initialize($config_path);
            $root_dir = Container::get('config')->getValue('app.root_dir');
            $model_path = $root_dir . '/Model/';
            $conn = $input->getOption('connection') ? $input->getOption('connection') : 'default';
            $json = Migrator::getTableDefsJson(Container::get("pdo.${conn}"));
            $tables = Migrator::getTables(Container::get("pdo.${conn}"));
            $models = [];
            foreach($tables as $table) {
                $class = ucfirst(strtr(ucwords(strtr($table, ['_' => ' '])), [' ' => '']));
                $models[$class.'.php'] = $this->createModelCode($table, $class);
            }

            $rehearsal = $input->getOption('rehearsal');
            if ($rehearsal) {
                echo $json . PHP_EOL;
                foreach($models as $file => $code) {
                    echo $code . PHP_EOL;
                }
            } else {
                file_put_contents($model_path . 'schema.json', $json);
                foreach($models as $file => $code) {
                    file_put_contents($model_path . $file, $code);
                }
            }
        } catch (Exception $e) {
            $logger->error($e->getMessage());
        }
    }

    public function createModelCode($table, $class)
    {
        $tpl = '<?php'                                                     . PHP_EOL;
        $tpl.= 'namespace Api\Model;'                                      . PHP_EOL . PHP_EOL;
        $tpl.= 'use Burdock\DataModel\Model;'                              . PHP_EOL;
        $tpl.= 'use Burdock\DokuApi\Container;'                            . PHP_EOL . PHP_EOL;
        $tpl.= "class ${class} extends Model"                              . PHP_EOL;
        $tpl.= '{'                                                         . PHP_EOL;
        $tpl.= "    protected static \$table_name = '${table}';"         . PHP_EOL;
        $tpl.= '    protected static $soft_delete_field = \'deleted_at\';' . PHP_EOL;
        $tpl.= '}'                                                         . PHP_EOL;
        $tpl.= 'BaseTable::loadSchema(Container::get(\'schema\'));'        . PHP_EOL;
        return $tpl;
    }
}