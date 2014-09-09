<?php
/**
 * Created by PhpStorm.
 * User: kostanevazno
 * Date: 17.07.14
 * Time: 0:20
 */

namespace greengo\yii2images;


trait ModuleTrait
{
    /**
     * @var null|\greengo\yii2images\Module
     */
    private $_module;

    /**
     * @return null|\greengo\yii2images\Module
     */
    protected function getModule()
    {
        if ($this->_module == null) {
            $this->_module = \Yii::$app->getModule('yii2images');
        }

        return $this->_module;
    }
}