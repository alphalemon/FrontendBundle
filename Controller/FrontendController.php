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

namespace AlphaLemon\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use AlphaLemon\ThemeEngineBundle\Core\Event\PageRenderer\BeforePageRenderingEvent;
use AlphaLemon\ThemeEngineBundle\Core\Event\PageRendererEvents;
use AlphaLemon\PageTreeBundle\Core\Tools\AlToolkit;

use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Defines the base controller application should inherit from
 *
 * @author AlphaLemon <info@alphalemon.com>
 */
abstract class FrontendController extends Controller
{
    public function showAction()
    {
        return $this->renderPage();
    }

    protected function renderPage()
    {
        $pageTree = $this->container->get('al_page_tree');
        if($pageTree != null)
        {
            $request = $this->container->get('request');
            
            if(null === $pageTree->getThemeName() || null === $pageTree->getTemplateName())
            {
                throw new RuntimeException('Something went wrong during the loading of the requested page. This is usually due to a routing misconfiguration: check the route for the requested page');
            }
            
            $dispatcher = $this->container->get('event_dispatcher');
            
            $event = new BeforePageRenderingEvent($this->container->get('request'), $pageTree);
            $dispatcher->dispatch(PageRendererEvents::BEFORE_RENDER_PAGE, $event);  
            $pageTree = $event->getPageTree();
            
            $eventName = sprintf('page_renderer.before_%s_rendering', $request->attributes->get('_locale'));             
            $dispatcher->dispatch($eventName, $event);
            $pageTree = $event->getPageTree();
            
            $eventName = sprintf('page_renderer.before_%s_rendering', $request->get('page'));             
            $dispatcher->dispatch($eventName, $event);
            $pageTree = $event->getPageTree();
            
            $template = sprintf('%s:Theme:%s.html.twig', $pageTree->getThemeName(), $pageTree->getTemplateName());
            
            $request = $this->container->get('request');
            $stylesheetsFileName = \sprintf('%s_%s_stylesheets.html.twig', $request->attributes->get('_locale'), $request->get('page'));
            if(!\file_exists(\sprintf('%sResources/views/Assets/%s', AlToolkit::locateResource($this->container, '@' . $this->container->getParameter('al.deploy_bundle')), $stylesheetsFileName))) $stylesheetsFileName = sprintf('%s_stylesheets.html.twig', $pageTree->getTemplateName());
            $stylesheetsTemplate = \sprintf('%s:Assets:%s', $this->container->getParameter('al.deploy_bundle'), $stylesheetsFileName);

            $javascriptsFileName = \sprintf('%s_%s_javascripts.html.twig', $request->attributes->get('_locale'), $request->get('page'));
            if(!\file_exists(\sprintf('%sResources/views/Assets/%s', AlToolkit::locateResource($this->container, '@' . $this->container->getParameter('al.deploy_bundle')), $javascriptsFileName))) $javascriptsFileName = sprintf('%s_javascripts.html.twig', $pageTree->getTemplateName());
            $javascriptsTemplate = \sprintf('%s:Assets:%s', $this->container->getParameter('al.deploy_bundle'), $javascriptsFileName);
            
            return $this->render($template, array('metatitle' => $pageTree->getMetatitle(),
                                                  'metadescription' => $pageTree->getMetaDescription(),
                                                  'metakeywords' => $pageTree->getMetaKeywords(),
                                                  'javascripts_template' => $javascriptsTemplate,
                                                  'stylesheets_template' => $stylesheetsTemplate,
                                                  'internal_stylesheets' => $pageTree->getInternalStylesheet(),
                                                  'internal_javascripts' => $pageTree->getInternalJavascript(),
                                                  'base_template' => $this->container->getParameter('althemes.base_template'),
                                                  'values' => $pageTree->getContents()));
        }
        else
        {
            $response = new Response();
            $response->setContent("CUSTOM ERROR PAGE");
            return $response;

            return $this->render('AlphaLemonPageTreeBundle:Error:ajax_error.html.twig', array('message' => $e->getMessage()), $response);
        }
    }
}

