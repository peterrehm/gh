<?php

namespace peterrehm\gh\Helper;

use Symfony\Component\Console\Helper\Helper;

class TemplatingHelper extends Helper
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct()
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/templates');
        $this->twig = new \Twig_Environment($loader, array('strict_variables' => true));
    }

    /**
     * @param string $template
     * @param array $parameters
     *
     * @return string
     */
    public function render($template, $parameters)
    {
        return $this->twig->render($template, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'templating';
    }
}
