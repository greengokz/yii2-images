<?php
/**
 * Created by PhpStorm.
 * User: kostanevazno
 * Date: 05.08.14
 * Time: 18:21
 *
 * TODO: check that placeholder is enable in module class
 * override methods
 */

namespace greengokz\yii2images\models;

/**
 * TODO: check path to save and all image method for placeholder
 */

use yii;

class PlaceHolder extends Image
{

    private $model_name = '';
    private $item_id = '';
    public $file_path = 'placeHolder.png';
    public $url_alias = 'placeHolder';
    public $is_image = false;


    /*  public function getUrl($size = false){
          $url = $this->getModule()->placeHolderUrl;
          if(!$url){
              throw new \Exception('PlaceHolder image must have url setting!!!');
          }
          return $url;
      }*/

    public function getPathToOrigin()
    {

        $url = Yii::getAlias($this->getModule()->placeHolderPath);
        if (!$url) {
            throw new \Exception('PlaceHolder image must have path setting!!!');
        }
        return $url;
    }

    protected  function getSubDur(){
        return 'placeHolder';
    }
    public function setMain($is_main = true){
        throw new yii\base\Exception('You must not set placeHolder as main image!!!');
    }

}

