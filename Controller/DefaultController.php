<?php

namespace KFI\BackupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('KFIBackupBundle:Default:index.html.twig', array('name' => $name));
    }
}
