<?php

namespace DataDog\PagerBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use DataDog\PagerBundle\Pagination;

class PaginationExtension extends \Twig_Extension
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $defaults = [
            'is_safe' => ['html'],
            'needs_environment' => true,
        ];

        return [
            'filter_uri' => new \Twig_Function_Method($this, 'filterUri'),
            'filter_is_active' => new \Twig_Function_Method($this, 'filterIsActive'),

            'filter_select' => new \Twig_Function_Method($this, 'filterSelect', $defaults),
            'filter_search' => new \Twig_Function_Method($this, 'filterSearch', $defaults),

            'sorter_link' => new \Twig_Function_Method($this, 'sorterLink', $defaults),

            'pagination' => new \Twig_Function_Method($this, 'pagination', $defaults),
        ];
    }

    private function mergeRecursive($a, $b)
    {
        if (!is_array($a) or !is_array($b)) {
            return $b;
        }
        foreach ($b AS $k => $v) {
            $a[$k] = $this->mergeRecursive(@$a[$k], $v);
        }
        return $a;
    }

    public function sorterLink(\Twig_Environment $twig, Pagination $pagination, $key, $title)
    {
        $params = $pagination->query();
        $direction = 'asc';
        $class = 'sorting';
        if (isset($params['sorters'][$key])) {
            $direction = strtoupper($params['sorters'][$key]) === 'ASC' ? 'DESC' : 'ASC';
            $class = strtolower($direction);
        }
        // @NOTE: here multiple sorters can be used if sorters are merged from parameters
        // but not overwritten
        $uri = $this->router->generate($pagination->route(), array_merge($pagination->query(), ['sorters' => [$key => $direction]]));

        return $twig->render(
            'DataDogPagerBundle::sorters/link.html.twig',
            compact('key', 'pagination', 'title', 'uri', 'class')
        );
    }

    public function filterSelect(\Twig_Environment $twig, Pagination $pagination, $key, array $options)
    {
        return $twig->render('DataDogPagerBundle::filters/select.html.twig', compact('key', 'pagination', 'options'));
    }

    public function filterSearch(\Twig_Environment $twig, Pagination $pagination, $key)
    {
        $value = isset($pagination->query()['filters'][$key]) ? $pagination->query()['filters'][$key] : '';
        return $twig->render('DataDogPagerBundle::filters/search.html.twig', compact('key', 'pagination', 'value'));
    }

    public function filterUri(Pagination $pagination, $key, $value)
    {
        return $this->router->generate(
            $pagination->route(),
            $this->mergeRecursive($pagination->query(), ['filters' => [$key => $value]])
        );
    }

    public function filterIsActive(Pagination $pagination, $key, $value)
    {
        return isset($pagination->query()['filters'][$key]) && $pagination->query()['filters'][$key] === $value;
    }

    public function pagination(\Twig_Environment $twig, Pagination $pagination)
    {
        return $twig->render('DataDogPagerBundle::pagination.html.twig', compact('pagination'));
    }

    public function getName()
    {
        return 'datadog_pagination_extension';
    }
}
