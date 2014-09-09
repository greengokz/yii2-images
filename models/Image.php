<?php


/**
 * This is the model class for table "image".
 *
 * @property integer $id
 * @property string $file_path
 * @property integer $item_id
 * @property integer $is_main
 * @property string $model_name
 * @property string $url_alias
 */

namespace greengo\yii2images\models;

use Yii;
use yii\base\Exception;
use yii\helpers\Url;
use yii\helpers\BaseFileHelper;
use \greengo\yii2images\ModuleTrait;



class Image extends \yii\db\ActiveRecord
{
    use ModuleTrait;


    private $helper = false;



    public function clearCache(){
        $subDir = $this->getSubDur();

        $dirToRemove = $this->getModule()->getCachePath().DIRECTORY_SEPARATOR.$subDir;

        if(preg_match('/'.preg_quote($this->model_name, '/').DIRECTORY_SEPARATOR, $dirToRemove)){
            BaseFileHelper::removeDirectory($dirToRemove);

        }

        return true;
    }

    public function getExtension(){
        $ext = pathinfo($this->getPathToOrigin(), PATHINFO_EXTENSION);
        return $ext;
    }

    public function getUrl($size = false){
        $urlSize = ($size) ? '_'.$size : '';
        $url = Url::toRoute([
            '/'.$this->getModule()->id.'/images/image-by-item-and-alias',
            'item' => $this->model_name.$this->item_id,
            'dirtyAlias' =>  $this->url_alias.$urlSize.'.'.$this->getExtension()
        ]);

        return $url;
    }

    public function getPath($size = false){
        $urlSize = ($size) ? '_'.$size : '';
        $base = $this->getModule()->getCachePath();
        $sub = $this->getSubDur();

        $origin = $this->getPathToOrigin();

        $file_path = $base.DIRECTORY_SEPARATOR.
            $sub.DIRECTORY_SEPARATOR.$this->url_alias.$urlSize.'.'.pathinfo($origin, PATHINFO_EXTENSION);;

        if(!file_exists($file_path)){
            $this->createVersion($origin, $size);

            if(!file_exists($file_path)){
                throw new \Exception('Problem with image creating.');
            }
        }

        return $file_path;
    }

    public function getContent($size = false){
        return file_get_contents($this->getPath($size));
    }

    public function getPathToOrigin(){

        $base = $this->getModule()->getStorePath();

        $file_path = $base.DIRECTORY_SEPARATOR.$this->file_path;

        return $file_path;
    }

    public function getSizesWhen($sizeString){

        $size = $this->getModule()->parseSize($sizeString);
        if(!$size){
            throw new \Exception('Bad size..');
        }

        if($this->getModule()->graphicsLibrary == 'Imagick'){
            $image = new \Imagick($this->getPathToOrigin());
            $sizes = $image->getImageGeometry();
        }else{
            $image = new \abeautifulsite\SimpleImage($this->getPathToOrigin());
            $sizes['width'] = $image->get_width();
            $sizes['height'] = $image->get_height();
        }


        $imageWidth = $sizes['width'];
        $imageHeight = $sizes['height'];
        $newSizes = [];
        if(!$size['width']){
            $newWidth = $imageWidth*($size['height']/$imageHeight);
            $newSizes['width'] = intval($newWidth);
            $newSizes['heigth'] = $size['height'];
        }elseif(!$size['height']){
            $newHeight = intval($imageHeight*($size['width']/$imageWidth));
            $newSizes['width'] = $size['width'];
            $newSizes['heigth'] = $newHeight;
        }

        return $newSizes;
    }

    public function createVersion($imagePath, $sizeString = false)
    {
        if(strlen($this->url_alias)<1){
            throw new \Exception('Image without url_alias!');
        }

        $cachePath = $this->getModule()->getCachePath();
        $subDirPath = $this->getSubDur();
        $fileExtension =  pathinfo($this->file_path, PATHINFO_EXTENSION);

        if($sizeString){
            $sizePart = '_'.$sizeString;
        }else{
            $sizePart = '';
        }

        $pathToSave = $cachePath.'/'.$subDirPath.'/'.$this->url_alias.$sizePart.'.'.$fileExtension;

        BaseFileHelper::createDirectory(dirname($pathToSave), 0777, true);


        if($sizeString) {
            $size = $this->getModule()->parseSize($sizeString);
        }else{
            $size = false;
        }

            if($this->getModule()->graphicsLibrary == 'Imagick'){
                $image = new \Imagick($imagePath);
                $image->setImageCompressionQuality(100);

                if($size){
                    if($size['height'] && $size['width']){
                        $image->cropThumbnailImage($size['width'], $size['height']);
                    }elseif($size['height']){
                        $image->thumbnailImage(0, $size['height']);
                    }elseif($size['width']){
                        $image->thumbnailImage($size['width'], 0);
                    }else{
                        throw new \Exception('Something wrong with this->module->parseSize($sizeString)');
                    }
                }

                $image->writeImage($pathToSave);
            }else{

                $image = new \abeautifulsite\SimpleImage($imagePath);



                if($size){
                    if($size['height'] && $size['width']){

                        $image->thumbnail($size['width'], $size['height']);
                    }elseif($size['height']){
                        $image->fit_to_height($size['height']);
                    }elseif($size['width']){
                        $image->fit_to_width($size['width']);
                    }else{
                        throw new \Exception('Something wrong with this->module->parseSize($sizeString)');
                    }
                }

                //WaterMark
                if($this->getModule()->waterMark){

                    if(!file_exists(Yii::getAlias($this->getModule()->waterMark))){
                        throw new Exception('WaterMark not detected!');
                    }

                    $wmMaxWidth = intval($image->get_width()*0.4);
                    $wmMaxHeight = intval($image->get_height()*0.4);

                    $waterMarkPath = Yii::getAlias($this->getModule()->waterMark);

                    $waterMark = new \abeautifulsite\SimpleImage($waterMarkPath);



                    if(
                        $waterMark->get_height() > $wmMaxHeight
                        or
                        $waterMark->get_width() > $wmMaxWidth
                    ){

                        $waterMarkPath = $this->getModule()->getCachePath().DIRECTORY_SEPARATOR.
                            pathinfo($this->getModule()->waterMark)['filename'].
                            $wmMaxWidth.'x'.$wmMaxHeight.'.'.
                            pathinfo($this->getModule()->waterMark)['extension'];

                        //throw new Exception($waterMarkPath);
                        if(!file_exists($waterMarkPath)){
                            $waterMark->fit_to_width($wmMaxWidth);
                            $waterMark->save($waterMarkPath, 100);
                            if(!file_exists($waterMarkPath)){
                                throw new Exception('Cant save watermark to '.$waterMarkPath.'!!!');
                            }
                        }

                    }

                    $image->overlay($waterMarkPath, 'bottom right', .5, -10, -10);

                }

                $image->save($pathToSave, 100);
            }


        return $image;

    }


    public function setMain($is_main = true){
        if($is_main){
            $this->is_main = 1;
        }else{
            $this->is_main = 0;
        }

    }

    protected function getSubDur(){
        return $this->model_name. 's/' . $this->model_name.$this->item_id;
    }



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'image';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_path', 'item_id', 'model_name', 'url_alias'], 'required'],
            [['item_id', 'is_main'], 'integer'],
            [['file_path', 'url_alias'], 'string', 'max' => 400],
            [['model_name'], 'string', 'max' => 150]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_path' => 'File Path',
            'item_id' => 'Item ID',
            'is_main' => 'Is Main',
            'model_name' => 'Model Name',
            'url_alias' => 'Url Alias',
        ];
    }
}
