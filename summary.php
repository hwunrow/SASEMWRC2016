<?php
if(!defined("ROOT")){
    define("ROOT",$_SERVER['DOCUMENT_ROOT']);
}
include_once ROOT."/API/mvc.php";
require_once(ROOT."/API/https.php");
session_start();



class StateMachine{
  static function route(){
    if(empty($_SESSION["complete"]) || !$_SESSION["complete"]){
      session_destroy();
      header("Location: register.php");
    }
  }
}


function add(&$buf,$string){
  if(!empty($string)){
    $buf = $buf.$string."\n";
  }
}

function createMessage(){
    # Look at Session Data
    $start = "Thank you for registering to go to the SASE Regional Conference.\n\n";
    $buf = "";
    $body="Here is a summary of the information you have provided and a copy has been sent to your email.\n\n";
    
    $buf4 = "";
    if(isset($_SESSION["receipt"])){
      $buf4="Here is your Transaction Receipt\n\n";
      foreach($_SESSION["receipt"] as $key=>$value){
          add($buf4,$key.": ".$value);
        }
      add($buf4,"\n");
    }
    
    add($buf,"Status: ".$_SESSION["info"]["status"]);
    add($buf,"Name: ".$_SESSION["info"]["name"]);
    add($buf,"Email: ".$_SESSION["info"]["email"]);
    add($buf,"Phone: ".$_SESSION["info"]["phone"]);
    add($buf,"Affiliated Group: ".$_SESSION["info"]["school"]);
    add($buf,"Lodging: ".ucfirst($_SESSION["info"]["lodging"]));
    add($buf,"Year: ".$_SESSION["info"]["year"]);
    add($buf,"Major: ".$_SESSION["info"]["major"]);
    add($buf,"T-shirt size: ".$_SESSION["info"]["t_size"]);
    if(!empty($_SESSION["info"]["food_fri"])){
      add($buf,"Attending Friday Night");
    }
    
     if(!empty($_SESSION["info"]["food_sat_breakfast"])){
      add($buf,"Attending Saturday Breakfast");
    }
    
     if(!empty($_SESSION["info"]["food_sat_lunch"])){
      add($buf,"Attending Saturday Lunch");
    }
    
    add($buf,"Food Option: ".$_SESSION["info"]["food_sat_option"]);
    if(!empty($_SESSION["diet"])){
      add($buf,"Dietary Restrictions: ".$_SESSION["info"]["diet"]);
    }
    add($buf,"\n");
    
    $buf3 = "We will either be mailing your student chapter or you personally for 
    hotel information in the future. Stay tuned.\n\n";
    
    $buf2 = "To contact us about any problems you may be experiencing please send an 
    email to nguy1952@umn.edu using the information: \n\n";
    
    add($buf2,"Name: ".$_SESSION["info"]["name"]);
    add($buf2,"Email: ".$_SESSION["info"]["email"]);
    
    if($_SESSION["info"]["school"] != "High School" || $_SESSION["status"] != "student"){
      add($buf2,"Transaction ID: ".$_SESSION["info"]["transactionID"]);
    }
    return $start.$body.$buf.$buf4.$buf3.$buf2;
  }

class Email{
  static function send(){
    $msg = createMessage();
    mail($_SESSION["info"]["email"],"Registration Receipt",$msg,null,'-fwebmaster@saseumn.org');
  }
}


## Display Information
function content(){
  $msg = createMessage();
  $msg = nl2br($msg);
  ?>
  <div class="container">
    <div class="row">
      <div class="col s12">
        <p class="flow-text">
          
          <?php echo $msg; ?>
        </p>
        
      </div>
    </div>
  </div>
  <?php
  # Kill everything with fire.
  session_destroy();
  session_unset();
  unset($_SESSION["info"]);
  unset($_SESSION["complete"]);
}


## Email Receipt
StateMachine::route();
Email::send();

include ROOT."/website/elements/base.php"
?>


