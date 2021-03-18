<?php
if(!isset($_GET["width"]) or !isset($_GET["height"]) or !isset($_GET["resources"]) )
{

exit("
Il faut <strong>obligatoirement</strong> fournir les parametres <strong>resources</strong>, <strong>width</strong> et <strong>height</strong>.<br/><br/>
Les parametres <strong>days</strong> et <strong>weeks</strong> sont <strong>optionnels</strong> <br/>
Par defaut lorsque days et weeks sont <strong>absents</strong> on affiche le jour ''d'aujourdhui''(i.e. <strong>change automatiquement chaque jour</strong>) de la semaine en cours (i.e. <strong>change automatiquement chaque semaine</strong>).<br/><br/>
Dans le cas ou il y aurait plusieurs valeurs pour un parametre, il faut les separer par des <strong>virgules</strong><br/><br/>
Exemples de parametres :  <br/>
<table>
<tr><td>?resources=4804&width=480&height=1080 </td><td> pour afficher la ressource 4804 d'aujourd'hui avec une largeur de 480px et une hauteur de 1080px </td></tr>
<tr><td>?resources=4804&width=480&height=1080&days=0 </td><td> pour toujours afficher le <strong>lundi</strong> de la semaine en cours </td></tr>
<tr><td>?resources=4804&width=480&height=1080&days=0,1,2,3,4 </td><td> pour toujours afficher les 5 jours (du lundi au vendredi) de la semaine en cours </td></tr>
<tr><td>?resources=4804&width=480&height=1080&weeks=7 </td><td> pour afficher ''aujourd'hui'' de la semaine 7 </td></tr>
<tr><td>?resources=4804&width=480&height=1080&days=0&weeks=7 </td><td> pour toujours afficher le lundi de la semaine 7 </td></tr>

");

}


if(isset($_GET["width"]))
{
  if(is_numeric($_GET["width"]))
  {
    $width=(int)$_GET["width"];
  }
  else
  {
    exit("La largeur de l'image doit etre un entier\n");
  }
}
else
{
  exit("Il faut fournir un parametre 'width' qui contient la largeur de l'image a afficher\n");
}


if(isset($_GET["height"]))
{
  if(is_numeric($_GET["height"]))
  {
    $height=(int)$_GET["height"];
  }
  else
  {
    exit("La hauteur de l'image doit etre un entier\n");
  }
}
else
{
  exit("Il faut fournir un parametre 'height' qui contient la hauteur de l'image a afficher\n");
}



if(isset($_GET["resources"]))
{
  $resources= explode( ',', $_GET["resources"]);
  foreach($resources as &$resource)
  {
    if(is_numeric($resource))
    {
      $resource=(int)$resource;
    }
    else
    {
      exit("L'id de la ressource doit etre un entier\n");
    }
  }
  $resources=implode(",", $resources);
}
else
{
  exit("Il faut fournir un parametre 'resources' qui contient le numero de la ressource a afficher\n");
}

include 'config.php';

if(isset($_GET["days"]))
{
  $days= explode( ',', $_GET["days"]);
  foreach($days as &$day)
  {
    if(is_numeric($day))
    {
      $day=(int)$day;
    }
    else
    {
      exit("Le jour doit etre un entier ou une serie d'entier separe par des virgules\n");
    }
  }
  $days=implode(",", $days);
}
else //Si l'on ne fourni pas de days, on calcul le jour du jour
{
  $days=date('w');
  $days=($days-1)%7; //dans php le lundi=1 alors que dans adesoft lundi=0
}


if(isset($_GET["weeks"]))
{
  $weeks= explode( ',', $_GET["weeks"]);
  foreach($weeks as &$week)
  {
    if(is_numeric($week))
    {
      $week=(int)$week;
    }
    else
    {
      exit("Le jour doit etre un entier ou une serie d'entier separe par des virgules\n");
    }
  }
  $weeks=implode(",", $weeks);
}
else //Si l'on ne fourni pas de weeks, on calcul la semaine
{
  $date_maintenant= new DateTime("NOW");
  //echo $date_debut_projet->format('Y-m-d H:i:s')."\n";
  //echo $date_maintenant->format('Y-m-d H:i:s')."\n";
  //difference :
  $interval= date_diff($date_debut_projet, $date_maintenant);

  $nb_jours=$interval->format('%a');
  $weeks=($nb_jours-($nb_jours%7))/7; //correspond au numero de la semaine en cours que l'on veut afficher
}

//On verifie si la resource demandé existe deja ou non
//Si elle existe et qu'elle est assez recente, on la recupere, sinon on la remplace par une nouvelle version
$dossier_images="stock_images";
$nom_fichier=$resources."_".$width."_".$height."_".$weeks."_".$days;

$chemin_complet=$dossier_images."/".$nom_fichier;

//on crée l'image si elle n'existe pas ou qu'elle est trop vieille
if(!file_exists($chemin_complet) or ((time()-filemtime($chemin_complet))>($duree_refresh*60)))
{
//  echo "l'image n'existe pas ou a ete modifiee il y a plus de ".$duree_refresh." minutes, on (re)cree l'image <br>\n";

  $url= $url_adesoft."/jsp/webapi?function=connect&login=".$login_adesoft."&password=".$mdp_adesoft;

  $xml = file_get_contents($url, false);
  $xml = simplexml_load_string($xml);  //besoin d'installer php7.0-xml
  //print_r($xml);

  $sessionId=$xml['id'];  //Ne pas modifier, necessaire pour la deconnexion

  $url= $url_adesoft."/jsp/webapi?sessionId=".$sessionId."&function=setProject&projectId=".$projectId;

  $xml = file_get_contents($url, false);
  $xml = simplexml_load_string($xml);
  //print_r($xml);

  $url= $url_adesoft."/jsp/webapi?sessionId=".$sessionId."&function=imageET&resources=".$resources."&width=".$width."&height=".$height."&weeks=".$weeks."&days=".$days;

  //On stock l'image sur le disque dur
  file_put_contents($chemin_complet,file_get_contents($url));

  //On se deconnecte du serveur
  $url=$url_adesoft."/jsp/webapi?function=disconnect&sessionId=".$sessionId;

  $xml = file_get_contents($url, false);
  $xml = simplexml_load_string($xml);
  //print_r($xml);

}


$image=file_get_contents($chemin_complet);

?>

<!DOCTYPE html>
<html>
<head>
<style>
img {
  max-width: 100%;
  height: auto;
}
</style>
</head>
<body>
  <?php  echo '<img src="' . $chemin_complet . '">'; ?>
</body>
</html>
