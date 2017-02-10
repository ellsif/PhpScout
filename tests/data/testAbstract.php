<?php

namespace ellsif\WelCoMeS;


use ellsif\Form;

/**
 * Serviceの基底クラス。
 */
abstract class Service
{

    public function __construct()
    {

    }

    /**
     * 認証を行い、権限があればページを表示する。
     * 出来るだけ継承先では本メソッドを利用してほしい。
    public function show(array $params)
    {
    if (!$this->authenticate($params)) {
    throw new \Error('Not Authorized', 401);
    }

    $config = Config::getInstance();
    /*
    $_viewPath = $action;
    if (!$viewPath) {
    $_viewPath = $config->dirView() . $this->getName() . '/' . $action . '.php';
    }
    $config->varService(get_class($this));
    if ($this->$action($_viewPath, $data) && \ellsif\isPost()) {
    // 予約されたフォームを完了させる
    Form::passReserve();
    }
    }
     */


    /**
     * ページの名前を取得。
     * SomeNamePage -> someName
     *
     * @return string
     */
    protected function getName() :string
    {
        $class = get_class($this);
        $class = substr($class, strrpos($class, '\\') + 1, -4);
        return strtolower((substr($class, 0, 1))) . substr($class, 1);
    }

    protected function requireHelpers()
    {
        $config = Config::getInstance();
        require_once $config->dirSystem() . '/functions/helper.php';
        require_once $config->dirSystem() . '/classes/core/Template.php';
        require_once $config->dirSystem() . '/classes/core/HtmlTemplate.php';
        require_once $config->dirView() . '/admin/helper.php';
    }
}