<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 14:33
 */

use \Slim\View;


class TwigView extends \Slim\View {
    /**
     * Twig loader
     *
     * @var \Twig_LoaderInterface
     */
    protected $loader;

    /**
     * Twig environment
     *
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * Create new Twig view
     *
     * @param string $path     Path to templates directory
     * @param array  $settings Twig environment settings
     */
    public function __construct($path, $settings = [])
    {
        parent::__construct();
        $this->loader = new \Twig_Loader_Filesystem($path);
        $this->environment = new \Twig_Environment($this->loader, $settings);
    }

    public function render($template, $data = null)
    {
        if (!is_array($data))   {
            $data = $this->getData();
        }
        return $this->environment->render($template, $data);
    }

}
