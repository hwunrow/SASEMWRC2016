<?php
if(!defined("ROOT")){
    define("ROOT",$_SERVER['DOCUMENT_ROOT']);
}
include_once ROOT."/API/mvc.php";
require(ROOT."/API/https.php");
include(ROOT."/API/modules/mwrc-module.php");
session_start();

function ProgressMeter($state){
  ?>
    <div class="row">
      <div class="col s6 center-align">
        Register
      </div>
      <div class="col s6 grey-text center-align">
        Payment
      </div>
    </div>
    <div class='row'>
      <div class="col s12">
      <?php 
      if($state == "register"){
        ?>
        <div class="col s6">        
          <div class="progress  teal lighten-5">
            <div class="determinate sase-blue" style="width: 100%"></div>
          </div>
        </div>
        <div class="col s6">        
          <div class="progress  teal lighten-5">
            <div class="determinate sase-blue" style="width: 0%"></div>
          </div>
        </div>
        <?php
      }else if($state="payment"){
        ?>
        <div class="col s6">        
          <div class="progress  teal lighten-5">
            <div class="determinate sase-blue" style="width: 100%"></div>
          </div>
        </div>
        
        <div class="col s6">        
          <div class="progress  teal lighten-5">
            <div class="determinate sase-blue" style="width: 100%"></div>
          </div>
        </div>
        <?php
      }
      ?>
      </div>
    </div>
  <?php
}
function setup(){
  $_POST["status"] = "Student";
  $_POST["name"] = "Danh";
  $_POST["email"] = "Danh@lol.com";
  $_POST["phone"] = "123-456-7890";
  $_POST["school"] = "University of Minnesota";
  $_POST["major"] = "some major";
  $_POST["year"] = "Freshman";
  $_POST["t_size"] = "Small";
  $_POST["food_fri"] = "some fri";
  $_POST["food_sat_breakfast"] = "some sat breakfast";
  $_POST["food_sat_lunch"] = "sat lunch";
  $_POST["food_sat_option"] = "Beef";
  $_POST["diet"] = "None";
}


$mwrc = new MWRC_Model();
if(!empty($_POST)){
  if(!empty($_POST["other"])){
    $_POST["school"] = $_POST["other"];
  }
  $res = $mwrc->checkAll($_POST);
  $status= $res["status"];
  $_POST= $res["data"];
}

if(empty($status) && isset($_POST) && !empty($_POST)){
  $_SESSION["info"] = $_POST;
  header("Location: checkout.php");
}

if(isset($_SESSION["info"])){
  $_POST = $_SESSION["info"];
}

function content(){
global $status;

if($status == null){
  $status = array();
}

$schools = array('University of Minnesota',
 'University of Iowa',
 'University of Wisconsin - Madison',
 'Northwestern University',
 'University of Illinois - Urbana Champaign',
 'Rose Hulman Institute of Technology',
 'University of Notre Dame',
 'Michigan State University',
 'University of Michigan - Ann Arbor',
 'University of Cincinnati',
 'University of Dayton',
 'Ohio State University',
 'Purdue University',
 'Kansas State University',
 'High School');
 
 $food = array("Smoked Hickory Beef Brisket","Santorini Chicken","Seitan Marsala (Vegetarian)");
 
function radio($name,$values){
  
  $inc= 1;
  foreach($values as $value){
  ?>
  <div class="col s12">
  <input id="<?php echo $name."-".$inc; ?>" type="radio" name="<?php echo $name; ?>" value="<?php echo $value ?>"
  <?php if($_POST[$name] == $value){echo "checked";} ?>>
  <label for="<?php echo $name."-".$inc; ?>"><?php echo $value; ?></label>
  </div>
  <?php
  $inc++;
  }
}


function error($status,$key){
  if(empty($_POST)){
    return;
  }
  if(array_key_exists($key,$status)){
  ?>
    <span class="status"><?php 
    if($status[$key] != 1){
      echo $status[$key];
    }else{
      echo "Error with input";
    }?></span>
  <?php 
  }
}

?>
<div class="container">
  <style>
    
    req::after{
      content:"*";
      color:red !important;
    }
    
    .status{
      width:100%;
      color:red;
      
    }
  </style>
  
<form id="register" action="register.php" method="post" class="section">
  
  <?php
    ProgressMeter("register");
  ?>
  
  <h4>SASE 2016 Midwest Regional Conference </h4>

  <div class="row">
    
  <div class="input-field col s6">
      <input type="text" name="name" placeholder="John Doe" id="name" value="<?php  echo $_POST["name"] ?>" required>  
      <label for="name">Name <req/></label>
      <?php error($status,"name");  ?>
    </div>
    
  <div class="input-field col s6">
    <input type="email" name="email" placeholder="JohnDoe@gmail.com" class="validate" id="email" value="<?php echo $_POST["email"] ?>" required>  
    <label for="email">Email <req/></label>
    <?php error($status,"email");  ?>
  </div>
  
  </div>
  <div class="row">
    <div class="input-field col s6">
      <input type="text" name="phone" placeholder="123-456-7890" id="phone" value="<?php  echo $_POST["phone"] ?>" required>  
      <label for="phone">Phone Number<req/></label>
      <?php error($status,"phone");  ?>
    </div>
    <?php 
    $valid = false;
    ?>
    <div class="input-field col s6">
      <select name="status">
        <option style="sase-blue" value=""></option>
        <option style="sase-blue" value="Student" <?php 
        if($_POST["status"] == "Student"){
          $valid =  true;
          echo "selected";
        } ?>>Student</option>
        <option style="sase-blue" value="Graduate"<?php 
        if($_POST["status"] == "Graduate"){
          $valid = true;
          echo "selected";
        } ?>>Graduate Student</option>
        <option style="sase-blue" value="Professional"<?php 
        if($_POST["status"] == "Professional"){
          $valid = true;
          echo "selected";
        } ?>>Professional</option>
        
        <option style="sase-blue" value="Other"<?php 
        if($_POST["status"] == "Other"){
          $valid = true;
          echo "selected";
        } ?>>Other</option>
      </select>
          <label for="status">Your current status <req></req></label>
      <?php 
      if(!$valid){
        $status["status"] = 1;
      }
      error($status,"status");
     ?>
    </div>
  </div>

<div class="row">
<div class="col s12">
  <p> Affiliated Group <req/><p>
</div>
<?php radio("school",$schools) ?>
<div class="col s3">
  <label for="other">Other School/Corporation</label>
  <input id="other" type="text" name="other" value="<?php
  if(!in_array($_POST["other"],$schools)){
    echo $_POST["other"];
  }
  ?>">
  <?php error($status,"other");  ?>
</div>

<div class="col s12">
  <div id="lodging">
      <label>Do you require lodging?</label>
      <input id="lodging1" type="radio" name="lodging" value="yes">
            <label for="lodging1">Yes</label>
      <input id="lodging2" type="radio" name="lodging" value="no">
            <label for="lodging2">No</label>
  </div>
</div>


</div>

<div class="row">
  <div class="input-field col s6">
    <label for="major">Major or Area Study <req/> </label>
    <input type="text" name="major" id="major" placeholder="e.g. Computer Engineering" value="<?php  echo $_POST["major"] ?>"  required/>
    <?php error($status,"major");  ?>
  </div>

    <?php 
    $valid = false;
    ?>
    <div class="input-field col s6">
    <select name="year">
      <option style="sase-blue" value ="" default></option>
      <option style="sase-blue" value="Freshman" <?php 
      if($_POST["year"] == "Freshman"){
        $valid =  true;
        echo "selected";
      } ?>>Freshman</option>
      <option style="sase-blue" value="Sophomore"<?php 
      if($_POST["year"] == "Sophomore"){
        $valid = true;
        echo "selected";
      } ?>>Sophomore</option>
       <option style="sase-blue" value="Junior"<?php 
      if($_POST["year"] == "Junior"){
        $valid = true;
        echo "selected";
      } ?>>Junior</option>
      <option style="sase-blue" value="Senior"<?php 
      if($_POST["year"] == "Senior"){
        $valid = true;
        echo "selected";
      } ?>>Senior</option>
      
      <option style="sase-blue" value="Graduate"<?php 
      if($_POST["year"] == "Graduate"){
        $valid = true;
        echo "selected";
      } ?>>Graduate</option>
      
      <option style="sase-blue" value="N/A"<?php 
      if($_POST["year"] == "N/A"){
        $valid = true;
        echo "selected";
      } ?>>N/A</option>
    </select>
        <label for="year">Year in School <req></req></label>
    <?php 
    if(!$valid){
      $status["year"] = 1;
    }
    error($status,"year");
  ?>
      
      
    </div>
    
    <?php 
    $valid = false;
    ?>
    <div class="input-field col s6">
    <select name="t_size">
      <option style="sase-blue" value default></option>
      <option style="sase-blue" value="Small" <?php 
      if($_POST["t_size"] == "Small"){
        $valid =  true;
        echo "selected";
      } ?>>Small</option>
      <option style="sase-blue" value="Medium"<?php 
      if($_POST["t_size"] == "Medium"){
        $valid = true;
        echo "selected";
      } ?>>Medium</option>
       <option style="sase-blue" value="Large"<?php 
      if($_POST["t_size"] == "Large"){
        $valid = true;
        echo "selected";
      } ?>>Large</option>
    </select>
        <label for="t_size">T-shirt Size <req></req></label>
    <?php 
    if(!$valid){
      $status["t_size"] = 1;
    }
    error($status,"t_size");
  ?>
  </div>
  </div>

<!-- Food -->
  
<div class="row">
  <div class="col s12">
      <h4 class="header">Food</h4>
  </div>

<div class="col s12">
  <p>
       Which meals session are you planning to attend?<req/>
      </p>
<input class="food" name="food_fri" type="checkbox" id="food_fri" value="true" 
<?php if($_POST["food_fri"]){
echo "checked";
}?>/>
<label for="food_fri">Friday Night, March 11, 2016</label>
</div>
<div class="col s12">
  <input class="food" name="food_sat_breakfast" type="checkbox" id="food_sat_breakfast" value="true"
  <?php if($_POST["food_sat_breakfast"]){
  echo "checked";
  }?>/>
  <label for="food_sat_breakfast">Saturday Morning, March 12, 2016</label>
</div>
<div class="col s12">
  <input class="food" name="food_sat_lunch" type="checkbox" id="food_sat_lunch" value="true"
  <?php if($_POST["food_sat_lunch"]){
  echo "checked";
  }?>/>
  <label for="food_sat_lunch">Saturday Lunch, March 12, 2016</label>
</div>
<div class="col s12">
  <?php error($status,"food_times");  ?>
</div>


<div class="col s12">
  <p>Saturday Lunch Entree <req></req> (<a data-target="modal1" href="#modal1" class="modal-trigger">Menu</a>)
  <br>
  <label>If you are planning to attend the Saturday Lunch, which entree would you prefer?</label>
  </p>
</div>

<?php radio("food_sat_option",$food); ?>

<div class="col s12">
  <?php error($status,"food_sat_option");  ?>
</div>

<div class="col s6">
  <label>Dietary Restrictions</label>
  <input type="text" name="diet" placeholder="Peanuts" value="<?php  echo $_POST["diet"] ?>">
  <?php error($status,"diet");  ?>
</div>
</div>

<div class="row">
  <div class="col s6">
  <label>Coupon Code</label>
  <input type="text" name="coupon" placeholder="Enter a Coupon Code" value="<?php  echo $_POST["coupon"] ?>">
  <?php error($status,"coupon");  ?>
  </div>
</div>

<!-- Registration -->
<button id="submit" class="btn sase-blue">Proceed</button>
</form>

<p><em>If you are experiencing any difficulties please contact our webmaster at nguy1952@umn.edu</em></p>
</div>

<div id="modal1" class="modal">
    <div class="modal-content">
      <div class="section">
        <h5>Smoked Hickory Beef Brisket</h5>
        <p>Smoked hickory brisket of beef with tangy BBQ sauce. Served with

            baked beans, potato salad, fresh baked rolls & butter.</p>
      </div>
      <div class="divider"></div>
      <div class="section">
        <h5>Santorini Chicken</h5>
        <p>Boneless breast of chicken grilled with lemon herb seasoning.  

                 Garnished with artichoke hearts, sundried tomatoes, & shredded 

                 fresh basil. Served with mixed green salad, rice pilaf & fresh 

                 baked rolls & butter.</p>
      </div>
      <div class="divider"></div>
      <div class="section">
        <h5>Seitan Marsala (Vegetarian)</h5>
        <p>Sautéed  mushrooms, shallots, garlic and marsala wine. 

                    Served with steamed broccoli, cauliflower, carrots, brown       

                    rice & fresh baked rolls.</p>
      </div>
    </div>
</div>



<script>

// Checks if at least one of the food times have been checked
$(document).ready(function(){
  $('select').material_select()
  $('.modal-trigger').leanModal();
  
  checkMealTimes();
  uncheckSchoolsAndShowLodging();
  unloadCheckedLodging();
  $("#lodging").hide();
  
  function checkMealTimes(){
    $("#register").submit(function(e){
      var numChecked = 0;
      $(".food").each(function(index,e){
        if(e.checked)
          numChecked++;
      })
      if(numChecked <= 0){
        e.preventDefault();
        alert("Please enter which meal times you are attending");
      }
    })
  }
  
  function uncheckSchoolsAndShowLodging(){
    // First track which school was checked.
    $("#other").focus(function(){
      $("input[name=school]").each(function(i,elem){
        elem.checked = false;
        }
      )
      $("#lodging").show(200);
    })
  }
  
  function unloadCheckedLodging(){
    $("input[name=school]").click(function(){
      $("#lodging").hide();
    })
  }
})



  
</script>

<?php  } 

  include ROOT."/website/elements/base.php";

?>
  