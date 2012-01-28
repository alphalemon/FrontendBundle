<?php
/*
 * This file is part of the AlphaLemon FrontendBundle and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 * 
 * @license    GPL LICENSE Version 2.0
 * 
 */

namespace AlphaLemon\FrontendBundle\Core\Listener;

use Symfony\Component\HttpFoundation\Request;
use AlRequestCore\Listener\AlRequestListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AlphaLemon\PageTreeBundle\Core\Tools\AlToolkit;

/**
 * Sets up the PageTree object for the requested page and language
 *
 * @author AlphaLemon <info@alphalemon.com>
 */
class AlFrontendRequestListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
    * Handles the event when notified or filtered.
    *
    * @param Event $event
    */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $kernel = $this->container->get('kernel');  
        if(strpos($kernel->getEnvironment(), 'alcms') !== false)
        {
            return;
        }
        
        $this->fillPageTree();
    }

    /**
     * Reads the from the
     */
    protected function fillPageTree()
    {
        $request = $this->container->get('request');        
        if($request->get('page') != null)
        {
            $pageTree = $this->container->get('al_page_tree');
            $retrievedContents = array();
            $fileContents = sprintf('%sResources/data/%s.xml', AlToolkit::locateResource($this->container, '@' . $this->container->getParameter('al.deploy_bundle')), $request->attributes->get('page'));
           
            $xml = simplexml_load_file($fileContents);
            $contents = $xml->body->contents;
            foreach($contents->children() as $slot)
            {
                $slotName = (string)$slot["name"];
                $content = (string)$slot;
                $retrievedContents["$slotName"][]["HtmlContent"] = \urldecode($this->container->get('translator')->trans($content, array(), $request->attributes->get('page')));
            }
            
            $pageTree->setThemeName($xml->template->theme);
            $pageTree->setTemplateName($xml->template->name);
            
            $metatags = $this->retrieveXmlNode($xml->header->metatags, $request->attributes->get('_locale'));
            $js = (array)$this->retrieveXmlNode($xml->header->javascripts, $request->attributes->get('_locale'));
            $css = (array)$this->retrieveXmlNode($xml->header->stylesheets, $request->attributes->get('_locale'));
            if(null === $metatags) $metatags = array();
            
            $pageTree->setMetatags($metatags);
            $pageTree->addJavascripts($js);
            $pageTree->addStylesheets($css);
            $pageTree->appendInternalJavascript($this->retrieveXmlNode($xml->header->internal_javascripts, $request->attributes->get('_locale')));
            $pageTree->appendInternalStylesheet($this->retrieveXmlNode($xml->header->internal_stylesheets, $request->attributes->get('_locale')));

            $pageTree->setContents($retrievedContents, true); 
        }
    }

    protected function retrieveXmlNode($node, $key)
    {
        foreach($node->children() as $k => $v)
        {
            if($k == $key)
            {
                if(count($v->children()) > 0)
                {
                    $result = array();
                    foreach($v->children() as $k1 => $v1)
                    {
                        $result[$k1] = (string)$v1;
                    }
                }
                else
                {
                    $result = (string)$v;
                }
                
                return $result;
            }
        }
        
        return null;
    }
}
