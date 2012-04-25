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
            $dispatcher = $this->container->get('event_dispatcher');
            
            // Dispatches the pre rendering events for current language and page
            $event = new BeforePageRenderingEvent($this->container->get('request'), $pageTree);
            $dispatcher->dispatch(PageRendererEvents::BEFORE_RENDER_PAGE, $event);  
            $pageTree = $event->getPageTree();
            
            $eventName = sprintf('page_renderer.before_%s_rendering', $request->attributes->get('_locale'));             
            $dispatcher->dispatch($eventName, $event);
            $pageTree = $event->getPageTree();
            
            $eventName = sprintf('page_renderer.before_%s_rendering', $request->get('page'));             
            $dispatcher->dispatch($eventName, $event);
            $pageTree = $event->getPageTree();
            
            // Renders the template
            $template = sprintf('%s:AlphaLemon:%s/%s.html.twig', $this->container->getParameter('al.deploy_bundle'), $request->attributes->get('_locale'), $request->get('page'));
            return $this->render($template, array('base_template' => $this->container->getParameter('althemes.base_template'), 'slots' => $pageTree->getContents()));            
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

