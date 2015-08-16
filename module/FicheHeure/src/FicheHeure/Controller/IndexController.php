<?php
/**
 * @Author: Ophelie
 * @Date:   2015-07-29 17:40:56
 * @Last Modified by:   Ophelie
 * @Last Modified time: 2015-08-14 15:28:34
 */

// module\FicheHeure\src\FicheHeure\Controller\IndexController.php

namespace FicheHeure\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
// EntityManager
use Doctrine\ORM\EntityManager;
// Session
use Zend\Session\Container;
use Personnel\Entity\Personnel;
use FicheHeure\Entity\SaisieHeureJournee;
use FicheHeure\Entity\SaisieHeureProjet;
use FicheHeure\Form\SaisieHeureJourneeForm;
use FicheHeure\Form\SaisieHeureForm;

class IndexController extends AbstractActionController
{
    /**
     * Entity Manager
     * @var DoctrineORMEntityManager
     */
    protected $em;

    public function getEntityManager()
    {
        if(null===$this->em)
        {
            $this->em=$this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        }
        return $this->em;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em=$em;
    }

    // Permet la redirection en cas d'utilisateur non authentifié à l'envoi
    // On utilise cette méthode car ce n'est pas possible de le faire dans le constructeur du controller
    // Faire en sorte de centraliser la méthode verifierConnexion() plutôt que la réécrire dans chaque controller, grâce aux gestionnaires d'évènement dans Module.php
	public function onDispatch(\Zend\Mvc\MvcEvent $e)
	{
	    $this->verifierConnexion();
	    return parent::onDispatch($e);
	}

	private function verifierConnexion($redirection=true)
    {
        // Récupération de la session utilisateur
        $utilisateur=new Container('utilisateur');

        if($utilisateur->offsetGet('connecte', false))
        {
            return true;
        }
        elseif($redirection)
        {
           $this->redirect()->toRoute('application_login');
           
        }
        else
        {
            return false;
        }
    }

    public function indexAction()
    {

    }

    public function editerficheheureAction()
    {
        // if(!$this->getRequest()->isXmlHttpRequest())
        // {
            // Récupération de l'EntityManager
            $em             =$this->getEntityManager();
            // Récupération du Service Manager
            $sm             =$this->getServiceLocator();
             // Récupération du traducteur
            $translator     =$sm->get('Translator');
            // Récupération de la requete
            $request        =$this->getRequest();

            $personnel      = new Personnel();
            $saisie         = new SaisieHeureProjet();
            $utilisateur    = new Container('utilisateur');
            // $utilisateurCourant = $utilisateur->offsetGet('identite');

            // $id = $utilisateur->offsetGet('id');
            // $utilisateurCourant = $em->getRepository('Personnel\Entity\Personnel')->find($id);
            // if($utilisateurCourant == null)
            //     throw new \Exception($translator->translate('Une erreur est survenue au chargement des heures.'));
        
            //Assignation de variables au layout
            $this->layout()->setVariables(array(
                'headTitle'         =>  $translator->translate('Fiche d\'heures'),
                'breadcrumbActive'  =>  $utilisateur->offsetGet('identite'),
                'route'             =>  array(),
                'action'            =>  'editerficheheure',
                'module'            =>  'fiche_heure',
                'plugins'           =>  array('fullcalendar','jquery-ui'),
            ));

            /* Initialisation des variables de la vue Calendar */

            $critere[] = 'personnel';
            $idAffaire = null;
            $idPersonnel = $utilisateur->offsetGet('id');

            $numeroAffaire = "";
            $recapitulatif = array();
            $saisiesHoraires = array();

            /* Récupération des variables transmises par la recherche en GET */

            if(isset($_GET['ref_personnel']) /*&& $_GET['ref_personnel'] != ""*/)
            {
                $idPersonnel = (int) $_GET['ref_personnel'];
            }
            if(isset($_GET['ref_affaire']) /*&& $_GET['ref_affaire'] != ""*/)
            {
                $idAffaire = (int) $_GET['ref_affaire'];
                $numeroAffaire = $_GET['numero_affaire'];
            }
            if(isset($_GET['critere']))
            {
                $critere = $_GET['critere'];
            }

            /* Récupération des saisies d'heures en réponse aux critères de recherche définis plus haut */

            $personnels = $personnel->getNomsPersonnels($this->getServiceLocator());
            switch($critere[0])
            {
                case 'projet':
                    $saisiesHoraires = $saisie->getSaisiesHeureCalendar($sm, $idPersonnel, $idAffaire);
                    $recapitulatif = $saisie->getRecapitulatifParPersonnel($idAffaire,$sm); // A CHANGER !!
                break;
                default: // = 'personnel'
                    $saisiesHoraires = $saisie->getSaisiesHeureCalendar($sm, $idPersonnel, $idAffaire); // a transformer en array compréhensible par la conversion JSON.
                    $recapitulatif = $saisie->getRecapitulatifParProjet($idPersonnel,$sm); // A CHANGER !!
                break;
            }

            return new ViewModel(array(
                'personnels' => $personnels,
                'critere' => $critere,
                'numeroAffaire' => $numeroAffaire,
                'refAffaire' => $idAffaire,
                'saisiesJson' => $saisiesHoraires,
                'recapitulatif'=> $recapitulatif,
                'idPersonnel' => $idPersonnel,
            ));
        // }
        
        // // S'il s'agit d'une recherche

        // $resultat       = array();
        // $centres        = isset($_GET['centres']) ? $_GET['centres'] : null;
        // $etatAffaire    = isset($_GET['etat']) ? $_GET['etat'] : null;
        // $projetSigne    = isset($_GET['projetSigne']) ? (bool) $_GET['projetSigne'] : null;
        // $motCle         = isset($_GET['motCle']) ? $_GET['motCle'] : null;

        // $resultat       = $affaire->getListeAffaire($this->getServiceLocator(), $motCle, $centres, $etatAffaire, $projetSigne);

        // return new JsonModel(array(
        //     'resultat'=>json_encode($resultat)
        // ));
    }

    public function formulairesaisiehoraireAction()
    {
        if($this->getRequest()->isXmlHttpRequest())
        {
            /************************************** Initialisation de variables **************************************/

            $statusForm=null;
            // Récupération de l'EntityManager
            $em = $this->getEntityManager();
            // Récupération du Service Manager
            $sm = $this->getServiceLocator();
             // Récupération du traducteur
            $translator = $sm->get('Translator');
            // Récupération de la requete
            $request = $this->getRequest();

            $utilisateur = new Container('utilisateur');

            /*********************************** Initialisation de la saisie heure ***********************************/

            $saisieHoraire = null;

            // On recupère la date en timestamp
            $date = $this->params()->fromRoute('date');
            list($y,$m,$d) = explode('-', $date); // Split day, month and year in chaines
            $timestamp = mktime(4, 0, 0, (int) $m, (int) $d, (int) $y); // Retourne un timestamp

            // On reccupère l'utilisateur courant
            $idPersonnel = $utilisateur->offsetGet('id');
            $utilisateurCourant = $em->getRepository('Personnel\Entity\Personnel')->find($idPersonnel);
            if($utilisateurCourant == null)
                throw new \Exception($translator->translate('Une erreur est survenue au chargement des horaires.'));

            // On recupère la saisie à partir de la date et du personnel
            $saisieHoraire = $em->getRepository('FicheHeure\Entity\SaisieHeureJournee')->findOneBy(array('date'=>$timestamp,'refPersonnel'=>$idPersonnel));
            if($saisieHoraire==null)
            {
                // On crée une nouvelle saisie d'horaires
                $saisieHoraire = new SaisieHeureJournee($utilisateurCourant,$date);
            }

            /******************************* Creation du formulaire de saisie d'heure *******************************/
            
            $form = new SaisieHeureJourneeForm($translator,$sm,$em,$request,$saisieHoraire);
            if($request->isPost())
            {
                $form->setData($request->getPost());

                if($form->isValid())
                {
                    $statusForm = true;
                    $saisieHoraire->exchangeArray($form->getData(),$em);

                    $saisieHeure = new SaisieHeureProjet;
                    $saisieHeure->exchangeArrayFromSaisieHoraire($form->getData(),$em);
                    $saisieHeure->setRefSaisieHoraire($saisieHoraire);

                    $em->persist($saisieHoraire);
                    $em->persist($saisieHeure);
                    $em->flush();

                    return new JsonModel(array(
                        'statut'    => $statusForm
                    ));
                }
                else // Sinon, on retourne les erreurs au formulaire qui les affiche
                {
                    $statusForm = false;
                    $errors     = $form->getMessages();

                    return new JsonModel(array(
                        'statut'=>$statusForm,
                        'reponse'=>$errors,
                    ));
                }
            }

            /************************** Affichage du formulaire sans le layout (en modal) **************************/

            $viewModel=new ViewModel;
            $viewModel->setVariables(array(
                'saisieHoraire'=>$saisieHoraire,
                'date'=>$date,
                'form'=>$form,
            ))->setTerminal(true);

            return $viewModel;
        }
        return $this->redirect()->toRoute('home');
    }

    public function formulairesaisieheureAction()
    {
        if($this->getRequest()->isXmlHttpRequest())
        {
            /* Initialisation de variables */

            $statusForm=null;
            // Récupération de l'EntityManager
            $em=$this->getEntityManager();
            // Récupération du Service Manager
            $sm=$this->getServiceLocator();
             // Récupération du traducteur
            $translator=$sm->get('Translator');
            // Récupération de la requete
            $request=$this->getRequest();

            /* Initialisation de la saisie heure */
            $saisieHeure = null;
            $id = $this->params()->fromRoute('id');
            if(!empty($id)) // Si l'ID de la saisie d'heures est transmis, on réccupère celui-ci
            {
                // Récupération de la saisie d'heures en BD
                $saisieHeure = $em->getRepository('FicheHeure\Entity\SaisieHeureProjet')->find($id);
                if($saisieHeure==null)
                    throw new \Exception($translator->translate('Une erreur est survenue au chargement du formulaire.'));
            }
            else // Sinon on crée une nouvelle saisie d'heures
            {
                $id = null;
                $saisieHeure = new SaisieHeureProjet;
            }

            /* Creation du formulaire de saisie d'heure */
            
            $form = new SaisieHeureForm($translator,$sm,$em,$request,$saisieHeure);
            if($request->isPost())
            {
                $form->setData($request->getPost());

                if($form->isValid())
                {
                    $statusForm = true;
                    $saisieHeure->exchangeArray($form->getData(),$em);
                    $em->persist($saisieHeure);
                    $em->flush($saisieHeure);

                    return new JsonModel(array(
                        'statut'    => $statusForm
                    ));
                }
                else // Sinon, on retourne les erreurs au formulaire qui les affiche
                {
                    $statusForm = false;
                    $errors     = $form->getMessages();

                    return new JsonModel(array(
                        'statut'=>$statusForm,
                        'reponse'=>$errors,
                    ));
                }
            }

            /* Affichage du formulaire sans le layout (en modal) */

            $viewModel=new ViewModel;
            $viewModel->setVariables(array(
                'saisieHeure'=>$saisieHeure,
                'form'=>$form,
                'id'=>$id
            ))->setTerminal(true);

            return $viewModel;
        }
        return $this->redirect()->toRoute('home');
    }

    public function supprimersaisieheureAction()
    {
        if($this->getRequest()->isXmlHttpRequest())
        {
            $id =(int)$this->params()->fromRoute('id');
            $em = $this->getEntityManager();
            $saisieHeure=$em->getRepository('FicheHeure\Entity\SaisieHeureProjet')->find($id);

            if($saisieHeure==null)
            {
                throw new \Exception($this->getServiceLocator()->get('Translator')->translate('Une erreur est survenue lors de la suppression.'));
            }

            // On supprime la saisieHeure ainsi que ses adresses et interlocuteurs
            $em->remove($saisieHeure);
            $em->flush();

            return new JsonModel(array(
                'statut' => true
            ));
        }
        return $this->redirect()->toRoute('home');
    }
}

?>