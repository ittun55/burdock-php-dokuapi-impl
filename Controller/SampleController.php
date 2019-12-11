<?php
namespace Api\Controller;

use Api\Model\BaseTable;
use Burdock\DokuApi\Container;
use Burdock\DokuApi\Controller\BaseController;

class SampleController extends BaseController
{
    private static $pdo = null;
    private static $logger = null;

    public static function initialize()
    {
        self::$pdo = Container::get('pdo.default');
        self::$logger = Container::get('logger.doku_api');
    }

    public static function index2()
    {
        header('content-type: application/json; charset=utf-8');
        $err = [ 'code' => 200, 'items' => [
            '_summary' => 'This is the three.'
        ]];
        echo json_encode($err, JSON_PRETTY_PRINT);
    }

    /**
     * @throws \Exception
     */
    public static function index()
    {
        BaseTable::setPDOInstance(self::$pdo);
        $items = BaseTable::find();
        header('content-type: application/json; charset=utf-8');
        $res = [ 'code' => 200, 'items' => $items];
        echo json_encode($res, JSON_PRETTY_PRINT);
    }
}