<?php

use yii\codeception\TestCase;
use Codeception\Util\Debug;
use yii\codeception\DbTestCase;
use Codeception\Util\Stub;
use greengo\yii2images\behaviors\ImageBehave;
use greengo\yii2images\models\Image;
use yii\db\Connection;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use org\bovigo\vfs\vfsStream;
use yii\helpers\BaseFileHelper;

class ImageTest extends TestCase
{

    protected $dbConnection;

    private $model;


    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
            static::markTestSkipped('PDO and SQLite extensions are required.');
        }
    }

    protected function setUp()
    {
        parent::setUp();
        $config = ArrayHelper::merge(require(Yii::getAlias($this->appConfig)), [
            'components' => [
                'db' => [
                    'class' => '\yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ]
            ],
            'modules' => [
                'greengoStore' => [
                    'class' => 'greengo\yii2images\Module',

                ],
            ],
        ]);

        $this->mockApplication($config);

        $columns = [
            'id' => 'pk',
            'name' => 'string',
        ];
        Yii::$app->getDb()->createCommand()->createTable('test_image_behavior', $columns)->execute();

        $columns = [
            'id' => 'pk',
            'file_path' => 'VARCHAR(400) NOT NULL',
            'item_id' => 'int(20) NOT NULL',
            'is_main' => 'int(1)',
            'model_name' => 'VARCHAR(150) NOT NULL',
            'url_alias' => 'VARCHAR(400) NOT NULL',
        ];
        Yii::$app->getDb()->createCommand()->createTable('image', $columns)->execute();


        /*vfsStream::setup('root');
        vfsStream::setup('root/Cache');*/
        $module = Yii::$app->getModule('greengoStore');
        //$module->imagesStorePath = vfsStream::url('root/Store');
        //$module->imagesCachePath = vfsStream::url('root/Cache');
        $module->imagesStorePath = Yii::getAlias('@app').'/tests/unit/data/imgStore';
        $module->imagesCachePath = Yii::getAlias('@app').'/tests/unit/data/imgCache';

        $this->model = new ActiveRecordImage();
        $this->model->name = 'testName';
        $this->model->save();

    }

    protected function tearDown()
    {

        BaseFileHelper::removeDirectory(Yii::getAlias('@app').'/tests/unit/data/imgStore');
        BaseFileHelper::removeDirectory(Yii::getAlias('@app').'/tests/unit/data/imgCache');
    }

    // tests
    public function testGetUrl()
    {


        $this->model->attachImage(__DIR__.'/data/testPicture.jpg');
        $this->model->attachImage(__DIR__.'/data/testPicture.jpg', true);
        $this->model->attachImage(__DIR__.'/data/testPicture.jpg');

        $img = $this->model->getImage();

        $ext = pathinfo($img->getPathToOrigin(), PATHINFO_EXTENSION);

        $baseUrl = '/app/web/index-test.php/greengoStore/images/image-by-item-and-alias?item=ActiveRecordImage1&dirtyAlias=';
        $this->assertEquals($img->getUrl(), $baseUrl.$img->url_alias.'.'.$ext);

        $this->assertEquals($img->getUrl('x100'), $baseUrl.$img->url_alias.'_x100.'.$ext);
        $this->assertEquals($img->getUrl('100x400'),
            $baseUrl.$img->url_alias.'_100x400.'.$ext);
    }

    public function testGetPath()
    {
        $this->model->attachImage(__DIR__.'/data/testPicture.jpg');
        $image = $this->model->getImage();

        //var_dump($image->getPath());die;

        $expectedFile = Yii::getAlias('@app').'/tests/unit/data/imgCache/ActiveRecordImages/ActiveRecordImage1/'.
            $image->url_alias.'.jpg';
        $this->assertEquals($image->getPath(),
            $expectedFile);

        $this->assertTrue(file_exists($expectedFile));


        $expectedFile = Yii::getAlias('@app').'/tests/unit/data/imgCache/ActiveRecordImages/ActiveRecordImage1/'.
            $image->url_alias.'_200x100.jpg';
        $this->assertEquals($image->getPath('200x100'),
            $expectedFile);

        $this->assertTrue(file_exists($expectedFile));


    }

}
class ActiveRecordImage extends ActiveRecord
{
    public function behaviors()
    {
        return [
            ImageBehave::className(),
        ];
    }

    public static function tableName()
    {
        return 'test_image_behavior';
    }

    public function getStorePath()
    {
        return  vfsStream::setup('testImagesDir');
    }
}