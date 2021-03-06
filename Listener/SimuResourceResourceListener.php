<?php

namespace CPASimUSante\SimuResourceBundle\Listener;

use JMS\DiExtraBundle\Annotation as DI;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Claroline\CoreBundle\Event\CopyResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;
use Claroline\CoreBundle\Event\CreateResourceEvent;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\DeleteResourceEvent;
use Claroline\CoreBundle\Event\DownloadResourceEvent;
use Claroline\CoreBundle\Event\CustomActionResourceEvent;
use Claroline\CoreBundle\Event\ExportResourceTemplateEvent;
use Claroline\CoreBundle\Event\ImportResourceTemplateEvent;
use Claroline\CoreBundle\Event\PluginOptionsEvent;

use CPASimUSante\SimuResourceBundle\Manager\SimuResourceManager;
use CPASimUSante\SimuResourceBundle\Entity\SimuResource;
use CPASimUSante\SimuResourceBundle\Form\SimuResourceType;
use CPASimUSante\SimuResourceBundle\Form\SimuResourceEditType;

/**
 *  @DI\Service()
 */
class SimuResourceResourceListener extends ContainerAware
{
    /**
     * @var null|Request
     */
    private $request;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;
    /**
     * @var SimuResourceManager
     */
    private $simuresourceManager;

    /**
     * @DI\InjectParams({
     *     "requestStack"= @DI\Inject("request_stack"),
     *     "httpKernel"= @DI\Inject("http_kernel"),
     *     "container"=@DI\Inject("service_container"),
     *     "simuresourceManager"=@DI\Inject("cpasimusante.plugin.manager.simuresource")
     * })
     */

    public function __construct(
        RequestStack $requestStack,
        HttpKernelInterface $httpKernel,
        ContainerInterface $container,
        SimuResourceManager $simuresourceManager
    )
    {
        $this->requestStack = $requestStack;    //for modal
        $this->request = $requestStack->getCurrentRequest();
        $this->httpKernel = $httpKernel;        //for modal
        //if use of "extends ContainerAware", no declaration of $container attribute
        $this->setContainer($container);
        $this->templating = $container->get('templating');
        $this->formfactory = $container->get('form.factory');
        $this->simuresourceManager = $simuresourceManager;
    }
    //-------------------------------
    // PLUGIN GENERAL SETTINGS
    //-------------------------------

    /**
     * @DI\Observe("plugin_options_simuresourcebundle")
     *
     * @param PluginOptionsEvent $event
     */
    public function onPluginConfigure(PluginOptionsEvent $event)
    {
        /*//retrieve the plugin manager with its Service name
        $pluginManager = $this->container->get("cpasimusante.plugin.manager.pluginconfig");
        $form = $pluginManager->getPluginconfigForm();
        //Send the form to the renderer
        $content = $this->templating->rendclearInterval(myTimer);er(
            'CPASimUSanteSimutoolsBundle:Tools:pluginconfig.html.twig',
            array(
                'form' => $form->createView()
            )
        );*/
        $content = "Some parameters";
        //PluginOptionsEvent require a setResponse()
        $event->setResponse(new Response($content));
        $event->stopPropagation();
    }

    //-------------------------------
    // RESOURCE SETTINGS
    //-------------------------------
    /**
     * Display the form when adding a new SimuResource in the Resource section
     */
    /**
     * @DI\Observe("create_form_cpasimusante_simuresource")
     *
     * @param CreateFormResourceEvent $event
     */
    public function onCreateForm(CreateFormResourceEvent $event)
    {
        $resource = new SimuResource();
        $resource->setOtherfield(45);
        $resource->setOtherfield2(55);
        $form = $this->formfactory->create(new SimuResourceType(), $resource);
        $content = $this->templating->render(
            //use this one if i want to override the generic template : 'ClarolineCoreBundle:Resource:createForm.html.twig',
            //i.e : the generic template displays all fields
            'CPASimUSanteSimuResourceBundle:SimuResource:createForm.html.twig',
            array(
                'form' => $form->createView(),
                'resourceType' => 'cpasimusante_simuresource'
            )
        );
        $event->setResponseContent($content);
        $event->stopPropagation();
    }
    /**
     * When creation form is sent, what is done ?
     */
    /**
     * @DI\Observe("create_cpasimusante_simuresource")
     *
     * @param CreateResourceEvent $event
     */
    public function onCreate(CreateResourceEvent $event)
    {
        $request = $this->container->get('request');
        $form = $this->formfactory->create(new SimuResourceType(), new SimuResource());

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->container->get('doctrine.orm.entity_manager');

            $resource = $form->getData();
            //the claroline Resource needs a name, we set it with whatever we have
            $resource->setName($resource->getField());

            $em->persist($resource);

            $event->setResources(array($resource));
            $event->stopPropagation();
            //exit the modal
            return;
        }

        $content = $this->templating->render(
            'CPASimUSanteSimuResourceBundle:SimuResource:createForm.html.twig',
            array(
                'form' => $form->createView(),
                'resourceType' => $event->getResourceType()
            )
        );
        $event->setErrorFormContent($content);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("open_cpasimusante_simuresource")
     *
     * @param OpenResourceEvent $event
     */
    public function onOpen(OpenResourceEvent $event)
    {
        //Redirection to the controller.
        $route = $this->container
            ->get('router')
            ->generate('cpasimusante_simuresource_resource_open',
                array(
                    'simuresourceId' => $event->getResource()->getId()
                ));
        $response = new RedirectResponse($route);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("delete_cpasimusante_simuresource")
     *
     * @param DeleteResourceEvent $event
     */
    public function onDelete(DeleteResourceEvent $event)
    {
        //In case other entities are dependant from this one, do the stuff before (delete or move ...)
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("copy_cpasimusante_simuresource")
     *
     * @param CopyResourceEvent $event
     */
    public function onCopy(CopyResourceEvent $event)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $resource = $event->getResource();
        //Retrieve the entity (as an object) from the repository
        $resourceNow = $em->getRepository('CPASimUSanteSimuResourceBundle:SimuResource')->find($resource->getId());

        //Copy the entity into a new one
        $resourceNew = new SimuResource();
        //custom entity fields
        $resourceNew->setField($resourceNow->getField());
        $resourceNew->setOtherfield($resourceNow->getOtherfield());
        //generic entity fields
        $resourceNew->setName($resourceNow->getName());
        //Save the entity
        $em->persist($resourceNew);
        $em->flush();
        //Set the copy (Claroline stuff)
        $event->setCopy($resourceNew);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("download_cpasimusante_simuresource")
     *
     * @param DownloadResourceEvent $event
     */
    public function onDownload(DownloadResourceEvent $event)
    {
        $path = '/path/to/dledfile';
        $event->setItem($path);
        $event->stopPropagation();
    }

    //-------------------------------
    // CUSTOM SETTINGS
    //-------------------------------
    /**
     * custom menu item for resource, defined in Resources/config/config.ylm
     */
    /**
     * @DI\Observe("dostuff_cpasimusante_simuresource")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onDostuff( \Claroline\CoreBundle\Event\CustomActionResourceEvent $event)
    {
        $content = $this->templating->render(
            'CPASimUSanteSimuResourceBundle:SimuResource:dostuff.html.twig',
            array(
                'node' => $event->getResource()    //needed to get context and display the breadcrumb
            )
        );
        $response = new Response($content);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * Do something in a modal window
     */
    /**
     * @DI\Observe("doinmodal_cpasimusante_simuresource")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onDoinmodal(CustomActionResourceEvent $event)
    {
        $resourceInstance = $event->getResource();
//        $resourceconfig = $this->simuresourceManager->getResourceConfig($resourceInstance);
/*
        //possibility 1 : all is done in listener
        $form = $this->container->get('form.factory')->create(new SimuResourceEditType(), $resourceInstance);
        $form->handleRequest($this->request);
        $content = $this->templating->render(
            'CPASimUSanteSimuResourceBundle:SimuResource:doinmodal.html.twig',
            array(
                'form' => $form->createView(),
                'node' => $event->getResource()->getResourceNode()      //here send the node object
            )
        );
        $event->setResponse(new Response($content));
        $event->stopPropagation();
*/
        //possibility 2 : logic sent to controller
        $route = $this->container
            ->get('router')
            ->generate('cpasimusante_simuresource_edit_form',
                array(
                    'node' => $event->getResource()->getResourceNode()->getId() //here send the id of the object
                ));
        $response = new RedirectResponse($route);
        $event->setResponse($response);
        $event->stopPropagation();

    }

    /**
     * Do something in a regular twig template
     */
    /**
     * @DI\Observe("updatesimuresourceinpage_cpasimusante_simuresource")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onUpdatesimuresourceinpage(CustomActionResourceEvent $event)
    {
        $resource =  $event->getResource();

        //BEGIN Send a mail to user and add a message in Claroline internal messagebox (Claroline/message-bundle)
        $subject = 'Some test subject';
        $body = 'Some dummy body';
        //get users to send the mail to
        $users = $this->container->get('cpasimusante.plugin.manager.general')->getUsersForResourceByRights($resource->getResourceNode(), 'open', false);
        //sender
        $from = $this->container->get('security.token_storage')->getToken()->getUser();

        //Add a message in message box
        $messageManager = $this->container->get('claroline.manager.message_manager');
        $message = $messageManager->create($body, $subject, $users, $from);
        $messageManager->send($message);

        //Send a mail - not tested with mailserver
        $mailer = $this->container->get('claroline.manager.mail_manager')->send($subject, $body, $users, $from);
        //END Send a mail to user

        //Add flashbag
        $this->request->getSession()->getFlashBag()->add( 'success', 'A message in a success "flashbag"'        );
        $this->request->getSession()->getFlashBag()->add( 'success', 'Another message in a success "flashbag"'  );
        $this->request->getSession()->getFlashBag()->add( 'error',   'A message in an error "flashbag"'         );

        $content = $this->templating->render(
            'CPASimUSanteSimuResourceBundle:SimuResource:updatesimuresourceinpage.html.twig',
            array(
                '_resource' => $resource,
                'entity'    => $resource,
                'users'     => $users,
                'from'      => $from
            )
        );
        $response = new Response($content);

        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * page to test log (display, filter ...)
     */
    /**
     * @DI\Observe("testlog_cpasimusante_simuresource")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onTestlog(CustomActionResourceEvent $event)
    {
        //send the logic to the controller method
        $route = $this->container
                ->get('router')
                ->generate('cpasimusante_simuresource_testlog',
                    array(
                        'node' => $event->getResource()->getResourceNode()->getId()
                    ));
        $response = new RedirectResponse($route);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * page to test file upload
     */
    /**
     * @DI\Observe("testupload_cpasimusante_simuresource")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onTestupload(CustomActionResourceEvent $event)
    {
        //send the logic to the controller method
        $route = $this->container
            ->get('router')
            ->generate('cpasimusante_simuresource_testupload',
                array(
                    'node' => $event->getResource()->getResourceNode()->getId()
                ));
        $response = new RedirectResponse($route);
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
