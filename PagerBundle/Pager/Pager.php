<?php

namespace Gloomy\PagerBundle\Pager;

//use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\QueryBuilder;

use Zend\Paginator\Paginator;

use Gloomy\PagerBundle\Pager\Wrapper;
use Gloomy\PagerBundle\Pager\Wrapper\ArrayWrapper;
use Gloomy\PagerBundle\Pager\Wrapper\NullWrapper;
use Gloomy\PagerBundle\Pager\Wrapper\QueryBuilderWrapper;

class Pager
{
    /**
     * @var \Zend\Paginator\Paginator\Paginator
     */
    protected $_paginator;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $_request;

    /**
     * Config
     */
    protected $_config;

    /**
     * Router
     */
    protected $_router;

    /**
     * Route
     */
    protected $_route;

    /**
     * Données à ajouter dans l'URL
     */
    protected $_addToURL;

    public function __construct($request, $router, $items, $route = null, array $config = array(), array $addToURL = array())
    {
        /**
         * Initialisation des variables du pager (Gère plusieurs pagers sur une même page)
         */
        static $pagerNum;

        $pagerVar           = (array_key_exists('pagerVar', $config)) ? $config['pagerVar'] : '_gp'.$pagerNum++;
        $defaultConfig      = array(    'pageVar'               => $pagerVar.'[p]',
                                        'orderByVar'            => $pagerVar.'[o]',
                                        'filtersVar'            => $pagerVar.'[f]',
                                        'itemsPerPageVar'       => $pagerVar.'[pp]',

                                        'pageRange'             => 5,
                                        'itemsPerPage'          => 10,
                                        'itemsPerPageChoices'   => array( 10, 20, 100, 500, 1000 )
                                        );
        $this->_config      = array_merge($defaultConfig, $config);
        if ( ! in_array( $this->_config['itemsPerPage'], $this->_config['itemsPerPageChoices'] ) ) {
            $this->_config['itemsPerPageChoices'][]    = $this->_config['itemsPerPage'];
            sort( $this->_config['itemsPerPageChoices'] );
        }

        $this->_request     = $request;
        $this->_router      = $router;
        $this->_route       = $route;
        $this->_addToURL    = $addToURL;

        /**
         * Création du Wrapper
         */
        $wrapper            = $this->createWrapper($items);

        /**
         * Création du Paginator
         */
        $this->_paginator   = new Paginator($wrapper);
        $this->_paginator->setCurrentPageNumber((int) $this->getValue('pageVar', 1));
        $this->_paginator->setItemCountPerPage((int) $this->getValue('itemsPerPageVar', $this->getConfig('itemsPerPage')));
        $this->_paginator->setPageRange((int) $this->getConfig('pageRange'));
    }

    protected function createWrapper($items)
    {
        if ($items instanceof Wrapper) {
            $wrapper        = $items;
        }
        elseif ($items instanceof QueryBuilder) {
            $wrapper        = new QueryBuilderWrapper($items);
        }
        elseif (is_array($items)) {
            $wrapper        = new ArrayWrapper($items);
        }
        else {
            $wrapper        = new NullWrapper((int) $items);
        }

        /**
         * Gestion des tris et des filtres
         */
        $orderBy            = $this->getValue('orderByVar');
        if (is_array($orderBy)) {
            $wrapper->setOrderBy($orderBy);
        }

        $filters            = $this->getValue('filtersVar');
        if (is_array($filters)) {
            $wrapper->setFilters(   $filters['f'],                                              // Le nom des champs
                                    $filters['v'],                                              // La valeur associée
                                    array_key_exists('o', $filters) ? $filters['o'] : array(),  // L'opérateur a utilisé
                                    array_key_exists('l', $filters) ? $filters['l'] : array()   // L'organisation logique (ET/OU)
                                    );
        }

        return $wrapper;
    }

    public function getConfig($option)
    {
        return $this->_config[$option];
    }

    public function getValue($option, $default = null)
    {
        return $this->_request->get($this->getConfig($option), $default, true);
    }

    public function getOrderBy()
    {
        return $this->getValue('orderByVar', array());
    }

    public function getFilters()
    {
        return $this->getValue('filtersVar', array());
    }

    public function getCurrentPageNumber()
    {
        return $this->_paginator->getCurrentPageNumber();
    }

    public function setCurrentPageNumber($page)
    {
        $this->_paginator->setCurrentPageNumber($page);
    }

    public function getItems()
    {
        return $this->_paginator->getCurrentItems();
    }

    public function getPages()
    {
        return $this->_paginator->getPages();
    }

    public function getItemsPerPage()
    {
        return $this->getValue('itemsPerPageVar', $this->getConfig('itemsPerPage'));
    }

    public function path($page = null, array $parameters = array())
    {
        $default            = array(    $this->getConfig('pageVar')         => $page ? (int) $page : $this->getCurrentPageNumber(),
                                        $this->getConfig('orderByVar')      => $this->getValue('orderByVar'),
                                        $this->getConfig('filtersVar')      => $this->getValue('filtersVar'),
                                        $this->getConfig('itemsPerPageVar') => $this->getValue('itemsPerPageVar')
                                        );

        $parameters         = array_merge($this->_addToURL, $default, $parameters);
        return $this->_router->generate($this->_route, $parameters, false);
    }

    public function pathOrderBy($orderBy, array $parameters = array(), $page = null)
    {
        $default            = array(    $this->getConfig('pageVar')         => $page ? (int) $page : $this->getCurrentPageNumber(),
                                        $this->getConfig('orderByVar')      => $orderBy,
                                        $this->getConfig('filtersVar')      => $this->getValue('filtersVar'),
                                        $this->getConfig('itemsPerPageVar') => $this->getValue('itemsPerPageVar')
                                        );

        $parameters         = array_merge($this->_addToURL, $default, $parameters);
        return $this->_router->generate($this->_route, $parameters, false);
    }

    public function pathForm($page = null, array $parameters = array())
    {
        $default            = array(    $this->getConfig('pageVar')         => $page ? (int) $page : $this->getCurrentPageNumber(),
                                        $this->getConfig('orderByVar')      => $this->getValue('orderByVar'),
                                        );

        $parameters         = array_merge($this->_addToURL, $default, $parameters);
        return $this->_router->generate($this->_route, $parameters, false);
    }
}