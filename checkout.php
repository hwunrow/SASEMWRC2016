<?php
if(!defined("ROOT")){
    define("ROOT",$_SERVER['DOCUMENT_ROOT']);
}
include_once ROOT."/API/mvc.php";
require_once(ROOT."/API/https.php");
require_once(ROOT."/API/modules/mwrc-module.php");
require_once(ROOT."/API/modules/braintree-module.php");
include ROOT."/API/logger.php";
session_start();
$braintree = new BraintreeWrapper();
$error = "";
StateMachine::route();




class StateMachine{
  static function route(){
    global $error;
    $nonce = $_POST["payment_method_nonce"];
    if(($_SESSION["info"]["school"] == "High School" && $_SESSION["info"]["status"] == "Student") || $_SESSION["info"]["coupon"] == "kiki2k16"){
      $mwrc = new MWRC_Model();
      $_SESSION["info"]["transactionID"] = $_SESSION["info"]["email"];
      $_SESSION["info"]["amount"] = "USD: 0";
      $_SESSION["info"]["date_paid"] = date("Y-m-d");
      $_SESSION["info"]["lodging"] = "no";
      $register = $mwrc->register($_SESSION["info"]);
      if($register){
        $_SESSION["complete"] = true;
        header("Location: summary.php");
        return;
      }else{
        $file = Logger::getFile("verification_errors.txt");
        Logger::log($file,$register);
        fclose($file);
        ?>
        Something went wrong with the registration process. Please go back to the <a href='registration.php'>registration page</a>
        and try again.
        <?php
        return;
      }
    }else if(!empty($nonce)){
      $amount = RegistrationFee()+HotelFee($_SESSION["info"])-Discount($_SESSION["info"]["coupon"]);
      $mwrc = new MWRC_Model();
      $result;
      $emailRegisteredFlag = false;
      if($mwrc->notExist($_SESSION["info"]["email"])){
        $result = Braintree_Transaction::sale([
        'amount' => $amount,
        'paymentMethodNonce' => $nonce
        ]);
      }else{
        $emailRegisteredFlag = true;
      }

      if($result && $result->success){
        $receipt = array();
        $receipt["Transaction ID"] = $result->transaction->_attributes["id"];
        $date = $result->transaction->_attributes["createdAt"]->format('Y-m-d H:i:s');
        $receipt["Transaction Date"]=date("Y-m-d h:m:s", strtotime($date) - 60 * 60 * 6);
        $receipt["Amount Paid"] =$result->transaction->_attributes["currencyIsoCode"].":".$result->transaction->_attributes["amount"];
        $receipt["Merchant"] = $result->transaction->_attributes["merchantAccountId"];
        $receipt["Status"] = $result->transaction->_attributes["status"];
        $_SESSION["info"]["transactionID"] = $receipt["Transaction ID"];
        $_SESSION["info"]["amount"] = $receipt["Amount Paid"];
        $_SESSION["info"]["date_paid"] = $receipt["Transaction Date"];
        //$_SESSION["school"]
        unset($_SESSION["info"]["other"]);

        $register = $mwrc->register($_SESSION["info"]);
        if($register["status"]){
          $settlement =Braintree_Transaction::submitForSettlement($receipt["Transaction ID"], $result->transaction->_attributes["amount"]);
          $_SESSION["receipt"] = $receipt;
          if($settlement->success){
            $_SESSION["receipt"]["Status"] = $settlement->transaction->_attributes["status"];
          }
          $_SESSION["complete"] = true;
          if(!array_key_exists("lodging",$_SESSION["info"]) && $_SESSION["info"]["school"] != "University of Minnesota"){
            $_SESSION["info"]["lodging"] = "yes";
          }
          
          // Go back 6 hours
          $_SESSION["info"]["date_paid"]=date("Y-m-d h:m:s", strtotime($time) - 60 * 60 * 6);
          header("Location: summary.php");
        }else{
          $file = Logger::getFile("register_errors.txt");
          $error = "Something went wrong with your registration.
          <br>We have not charged you any money, but please contact nguy1952@umn.edu.";
          Logger::log($file,$register);
          fclose($file);
          return;
        }
        
      } 
      else if($emailRegisteredFlag){
        $error = "This email has already been registered and payed for.";
      }
      else if ($result->errors->deepSize() > 0) {
        $file = Logger::getFile("verification_errors.txt");
        Logger::log($file,$result->errors);
        fclose($file);
        $error = "The system could not verify your payment";
      } else {
        $file = Logger::getFile("verification_errors.txt");
        Logger::log($file,[$result->transaction->processorSettlementResponseCode,$result->transaction->processorSettlementResponseText]);
        fclose($file);
        $error = "The system could not verify your payment";
      }
      return;
    }
    else if(empty($_SESSION["info"])){
      header("Location: register.php");
    }
  }
}

function HotelFee($data){
  $base = 40;
  if(($data["school"] == "High School" || $data["school"] == "University of Minnesota") || $data["lodging"] == "no"){
    return 0;
  }
  return $base;
}

function RegistrationFee(){
  
  // Look at Todays Date
  $currentDate = new DateTime();
  
  // Array of times
  $days = array(
    array(new DateTime("2015-12-31"),20),
    array(new DateTime("2016-2-12"),25)
  );
  foreach($days as $deadline){
     if($currentDate < $deadline[0]){
       return $deadline[1];
     }
  }
  return 25;
}

function Discount($code){
  $couponmap = array();
  $discount = 0;
  if(array_key_exists($code,$couponmap)){
    $discount = $couponmap[$code];
  }
  
  return $discount;
}


function content(){
  global $error;
  function runTests(){
    function testTransaction(){
      $_POST["payment_method_nonce"] = "success";
      $data = array();
      $data["status"] = "highschool";
      $data["name"] = "lol";
      $data["email"] = "Danh@gmail.com";
      $data["phone"] = "123-456-7890";
      $data["school"] = "University of Minnesota";
      $data["major"] = "some major";
      $data["year"] = "some year";
      $data["t_size"] = "some size";
      $data["innoservice"] = "test";
      $data["food_fri"] = "some fri";
      $data["food_sat_breakfast"] = "some sat breakfast";
      $data["food_sat_lunch"] = "sat lunch";
      $data["food_sat_option"] = "Beef";
      $data["diet"] = "diet";
      $session["info"] = $data;
      StateMachine::route();
    }
    
    function testNormal(){
      $data = array();
      $data["status"] = "highschool";
      $data["name"] = "lol";
      $data["email"] = "Danh@gmail.com";
      $data["phone"] = "123-456-7890";
      $data["school"] = "University of Minnesota";
      $data["major"] = "some major";
      $data["year"] = "some year";
      $data["t_size"] = "some size";
      $data["innoservice"] = "test";
      $data["food_fri"] = "some fri";
      $data["food_sat_breakfast"] = "some sat breakfast";
      $data["food_sat_lunch"] = "sat lunch";
      $data["food_sat_option"] = "Beef";
      $data["diet"] = "diet";
      $_SESSION["info"] = $data;
      StateMachine::route();
    }
    
    return;
  }
  
  
  
  
  
  
  function Summary($data){
  ?>
    <div class="col s6 offset-s3">
      <div class="card darken-1">
        <div class="card-content section">
          <h4 class="black-text center-align">Registration Details</h4>
          <div class="">
            Status: <span class="right"><?php echo $data["status"]; ?></span>
          </div>
          <div class="">
            Name: <span class="right"><?php echo $data["name"]; ?></span>
          </div>
          <div class="">
            Email: <span class="right"><?php echo $data["email"]; ?></span>
          </div>
           <div class="">
            Phone: <span class="right"><?php echo $data["phone"]; ?></span>
          </div>
          <div class="">
            Affiliated Group: <span class="right"><?php echo $data["school"]; ?></span>
          </div>
          <div class="">
            Lodging: <span class="right"><?php
            if(empty($data["lodging"])){
              echo "Yes";
            }else{
              echo $data["lodging"];
            }
            ?></span>
          </div>
          <div class="">
            Major: <span class="right"><?php echo $data["major"]; ?></span>
          </div>
           <div class="">
             Year in Schooling: <span class="right"><?php echo $data["year"]; ?></span>
          </div>
          <div class="">
            T-shirt Size:<span class="right"> <?php echo $data["t_size"]; ?></span>
          </div>
           <?php
            if(!empty($data["food_fri"])){
              ?>
              <div class="">
              Attending <span class="right"> <?php echo "Friday Night";?></span>
              </div><?php
            }?>
         <?php
            if(!empty($data["food_sat_breakfast"])){
              ?>
              <div class="">
              Attending <span class="right"> <?php echo "Saturday Breakfast";?></span>
              </div><?php
            }?>
        
            <?php
            if(!empty($data["food_sat_lunch"])){
              ?>
              <div class="">
              Attending <span class="right"> <?php echo "Saturday Lunch";?></span>
              </div><?php
            }?>
          <div class="">
            Food Option:<span class="right"> <?php echo $data["food_sat_option"]; ?></span>
          </div>
          <?php if(!empty($data["diet"])){ ?>
          <div class="">
             Dietary Restrictions:<span class="right"> <?php echo $data["diet"]; ?></span>
          </div>
          <?php } ?>
          
          <?php if(!empty($data["coupon"])){ ?>
          <div class="">
             Coupon Code:<span class="right"> <?php echo $data["coupon"]; ?></span>
          </div>
          <?php } ?>
          <div class="">&nbsp</div>
        </div>
      </div>
    </div>
  
  <?php
  }
  
  
  
  
  function OrderDetails($data){
  ?>
    <div class="col s6 offset-s3">
      <div class="card darken-1">
        <p>
        <div class="card-content section">
          <h4 class="black-text center-align">Order Summary</h4>
          <div class="col s12">
            Registration <span class="right">$<?php 
              $registerFee =  RegistrationFee();
              echo $registerFee;
            ?></span>
          </div>
          <?php
            $hotelFee = HotelFee($data);
            if($hotelFee){
              ?>
              <div class="col s12">
                Lodging for non-local students: <span class="right">$<?php echo $hotelFee; ?></span>
              </div>
              <?php 
            }
          ?>
          
          
          <?php
            $discount = Discount($data["coupon"]);
            if(!empty($data["coupon"])){
              if($discount > 0){
              ?> 
                <div class="col s12">
                Coupon '<?php echo $data["coupon"] ?>' <span class="right">$<?php echo "-".$discount; ?></span>
                </div>
              <?php 
            }else{
              ?>
                <div class="col s12">
                Invalid Coupon Code: <span class="right">$<?php echo 0; ?></span>
                </div>
              <?php
            }
          }
          ?>
          <div class="col s12 divider">
            
          </div>
          <div class="col s12">
            Total: <span class="right">$<?php 
            echo $registerFee+$hotelFee-$discount; ?></span>
          </div>
          
          
        </div>
        </p>
          <h5 class="black-text center-align">Payment Information</h5>
          <form id="checkout" method="post" action="checkout.php">
            <div id="payment-form"></div>
            <input type="submit" class="btn sase-blue center-align" value="Checkout">
            <div class="section"></div>
          </form>
      </div>
    </div>
  <?php 
  }
  
  function ProgressMeter($state){
    ?>
      <div class="row">
        <div class="col s6 grey-text center-align">
          <a href="register.php">
            Back to Registration Page
          </a>
        </div>
        <div class="col s6 center-align">
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
  
  ?>
  <div class="container">
  <div class="row">
  <?php 
  ProgressMeter("checkout");
  if($error != ""){
      ?>
      <div class="row">
        <div class="col s12">
          <span class="red-text"><?php echo $error; ?></span>
        </div>
      </div>
      <?php 
    }
  ?>
  </div>
  
  
  
  <div class="row">
    <div class="col s6 offset-s3">
      <div class="card darken-1">
        <div class="card-content">
          <h4 class="black-text center-align">Product Information</h4>
           <div class="section">
              <h5>Ticket Information</h5>
              <p>
                <div class="section">
                You are paying for registration to the 2016 SASE Midwest Regional Conference.
                Prices for the ticket are as shown:
                <table class="bordered">
                  <tr>
                    <td><b>Register before Dec. 31, 2015</b></td>
                    <td><b>$20</b></td>
                  </tr>
                  <tr>
                    <td><b>After Feb. 8, 2016 until the Day of the conference</b></td>
                    <td><b>$25</b></td>
                  </tr>
                </table>
                </div>
  
                <div class="section">
                  For registering you will receive:
                  <ul class="collection">
                    <b>
                    <li class="collection-item">Admittance into the conference</li>
                    <li class="collection-item">Opportunity to join workshops and attend discussions</li>
                    <li class="collection-item">A meal for all days that you attend</li>
                    <li class="collection-item">A free T-shirt</li>
                    <li class="collection-item">...and much more!</li>
                    </b>
                  </ul>
                </div>
              </p>
            </div>
            
            <div class="section">
              <h5>Hotel Information</h5>
              <p>The SASE UMN Chapter has closed a deal with 
              <a  target="_blank" href="http://www.commonshotel.com/university-minnesota-hotel.aspx"> The Commons Hotel</a>.
                This deal allows us to subsidize a portion of your lodging fee, giving you two nights for the price of one.
              <b>Lodging is required for those attending that do not live in the local area.</b>  
              </p>
              <br>
              <p>
              A room consists of 2 queen sized beds. Max 4 per room.
              We will be giving each chapter a certain amount of rooms based on how members they have
              registered.
              </p>
            </div>
          </div>
      </div>
    </div>
  
    <?php
    Summary($_SESSION["info"]); 
    
    ?>
    <?php OrderDetails($_SESSION["info"]); ?>
    
    <dic class="row">
    <div class="col s12">
      <p><a data-target="modal1" href="#modal1" class="modal-trigger">Refund and Cancellation Policy</a></p>
      <p>By placing your order, you agree to Saseumn.org's 
      <a data-target="modal2" href="#modal3" class="modal-trigger">privacy policy</a>
      and 
      <a data-target="modal2" href="#modal2" class="modal-trigger">conditions of use</a>.</p>
      <p><em>If you are experiancing any difficulties please contact our webmaster, Danh Nguyen</em></p>
      <p><em>Email: nguy1952@umn.edu </em></p>
      <p><em>Phone: 651-239-6815 </em></p>
    </div>
  </dic>
  
  </div>
  </div>
  
  
  
  
  <!-- Modal Structure -->
  <div id="modal1" class="modal">
    <div class="modal-content">
      <h4>Cancellation and Refund Policy</h4>
      <p><p>Based on when a cancellation request is received, attendees of the Midwest Regional Conference may receive a full or partial refund of the all fees (including the hotel fee and the conference registration fee).</p>
   
  
  <table class="bordered">
          <thead>
            <tr>
                <th >Date Cancellation Request was received</th>
                <th>Policy</th>
            </tr>
          </thead>
  
          <tbody>
            <tr>
              <td><b>By 12/30/15</b></td>
              <td>A cancellation request received by December 24th, 2015 will receive a full refund of all fees.</td>
            </tr>
            <tr>
              <td><b>12/31/15 - 2/12/16</b></td>
              <td>A cancellation request received after December 30th, 2015 and by February 12th, 2016 will receive a full refund if the request if received within ten days of the attendee’s date of registration or a 50% refund for the rest of the period.</td>
            </tr>
            <tr>
              <td><b>2/13/16 - Day of Conference</b></td>
              <td>All cancellation requests received after February 13th, 2016 up until the date of the conference are ineligible for reimbursement and will not be considered.</td>
  </table>
  </div>
  </div>
  
  <div id="modal3" class="modal">
    <div class="modal-content">
      <h4>Private Policy</h4>
      <p><p>This Privacy Policy governs the manner in which Society of Asian Scientists and Engineers - University of Minnesota Twin Cities Chapter collects, uses, maintains and discloses information collected from users (each, a "User") of the http://saseumn.org website ("Site").</p>
  
  <h4>Personal identification information</h4>
  <p>We may collect personal identification information from Users in a variety of ways, including, but not limited to, when Users visit our site, register on the site, place an order, fill out a form, and in connection with other activities, services, features or resources we make available on our Site.Users may be asked for, as appropriate, name, email address, phone number. Users may, however, visit our Site anonymously. We will collect personal identification information from Users only if they voluntarily submit such information to us. Users can always refuse to supply personally identification information, except that it may prevent them from engaging in certain Site related activities.</p>
  
  <h4>Non-personal identification information</h4>
  <p>We may collect non-personal identification information about Users whenever they interact with our Site. Non-personal identification information may include the browser name, the type of computer and technical information about Users means of connection to our Site, such as the operating system and the Internet service providers utilized and other similar information.</p>
  
  <h4>Web browser cookies</h4>
  <p>Our Site may use "cookies" to enhance User experience. User's web browser places cookies on their hard drive for record-keeping purposes and sometimes to track information about them. User may choose to set their web browser to refuse cookies, or to alert you when cookies are being sent. If they do so, note that some parts of the Site may not function properly.</p>
  
  <h4>How we use collected information</h4>
  <p>Society of Asian Scientists and Engineers - University of Minnesota Twin Cities Chapter may collect and use Users personal information for the following purposes:</p>
  <ul>
    <li>
      <i>To run and operate our Site</i><br/>
      We may need your information display content on the Site correctly.
    </li>
    <li>
      <i>To improve customer service</i><br/>
      Information you provide helps us respond to your customer service requests and support needs more efficiently.
    </li>
    <li>
      <i>To send periodic emails</i><br/>
      We may use the email address to send User information and updates pertaining to their order. It may also be used to respond to their inquiries, questions, and/or other requests. 
    </li>
  </ul>
  
  <h4>How we protect your information</h4>
  <p>We adopt appropriate data collection, storage and processing practices and security measures to protect against unauthorized access, alteration, disclosure or destruction of your personal information, username, password, transaction information and data stored on our Site.</p>
  
  <h4>Sharing your personal information</h4>
  <p>We do not sell, trade, or rent Users personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates and advertisers for the purposes outlined above. </p>
  
  <h4>Third party websites</h4>
  <p>Users may find advertising or other content on our Site that link to the sites and services of our partners, suppliers, advertisers, sponsors, licencors and other third parties. We do not control the content or links that appear on these sites and are not responsible for the practices employed by websites linked to or from our Site. In addition, these sites or services, including their content and links, may be constantly changing. These sites and services may have their own privacy policies and customer service policies. Browsing and interaction on any other website, including websites which have a link to our Site, is subject to that website's own terms and policies.</p>
  
  <h4>Changes to this privacy policy</h4>
  <p>Society of Asian Scientists and Engineers - University of Minnesota Twin Cities Chapter has the discretion to update this privacy policy at any time. When we do, we will post a notification on the main page of our Site. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect. You acknowledge and agree that it is your responsibility to review this privacy policy periodically and become aware of modifications.</p>
  
  <h4>Your acceptance of these terms</h4>
  <p>By using this Site, you signify your acceptance of this policy. If you do not agree to this policy, please do not use our Site. Your continued use of the Site following the posting of changes to this policy will be deemed your acceptance of those changes. This policy was generated using <a href="http://privacypolicies.com" target="_blank">PrivacyPolicies.Com</a></p>
  
  <h4>Contacting us</h4>
  <p>If you have any questions about this Privacy Policy, the practices of this site, or your dealings with this site, please contact us.</p>
  
  <p>This document was last updated on November 22, 2015</p></p>

  </div>
  </div>
  
  <div class="modal" id="modal2">
    <div class="modal-content">
       <h4>Terms of Use</h4>
      <p><h4>Terms of Service ("Terms")</h4>
  <p>Last updated: November 22, 2015</p>
  
  <p>Please read these Terms of Service ("Terms", "Terms of Service") carefully before using the saseumn.org website (the "Service") operated by Society of Asian Scientists And Engineers University of Minnesota Chapter ("us", "we", or "our").</p>
  
  <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service.</p>
  
  <p>By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service.</p>
  
  
<h4>Terms and Conditions</h4>

By agreeing to these Terms and Conditions you understand that you are registering to attend the SASE Midwest Regional Conference 2016 , and all fees associated with your registration and accommodation or event pass to the conference are payable in full as per payment terms. By registering and attending the conference each person acknowledges and agrees to the following restrictions as the criteria of being accepted to attend the conference.

<h4>I. Privacy Policy</h4>
<p>
For the privacy policy, please refer back to the payment page of registration for full list of terms. 
</p>

<h4>II. Payment Policy </h4>
<p>
All payments made prior to the conference must be paid in full to guarantee registration. Once payment has been received, an email confirmation and a receipted invoice. We accept all major credit and debit cards, but no cash or check. 
</p>

<h4>III. Refund and Cancellation Policy</h4>
<p>
Based on when a cancellation request is received, attendees of the Midwest Regional Conference may receive a full or partial refund of the all fees (including the hotel fee and the conference registration fee).

<table border="1">
<thead>
  <th>
  Date Cancellation Request was received
  </th>
  <th>Policy</th>
</thead>
<tbody>
  <tr>
    <td>By 12/30/15</td>
    <td>A cancellation request received by December 30th, 2015 will receive a full refund of all fees.</td>
  </tr>
  <tr>
    <td>
      12/31/15 - 2/12/16
    </td>
    <td>
      A cancellation request received after December 30th, 2015 and by February 12th, 2016 will receive a full refund if the request if received within ten days of the attendee’s date of registration or a 50% refund for the rest of the period.
    </td>
  </tr>
  <tr>
    <td>
      2/13/16 - Day of Conference
    </td>
    <td>
      All cancellation requests received after February 13th, 2016 up until the date of the conference are ineligible for reimbursement and will not be considered.
    </td>
  </tr>
</tbody>
</table>


Any full or partial refund will be processed and received within two weeks of the cancellation request. Cancellation requests may be submitted to <b>nguy1952@umn.edu</b>. Please include the following in your cancellation email
<ul>
  <li>
    Name
  </li>
  <li>
    Affiliated Group
  </li>
  <li>
    Registration details(Name,Email, and Transaction ID)
  </li>
  <li>
    Reason for cancellation
  </li>
</ul>
Failure to email a cancellation request before the final, partial refund deadline will result in a forfeiture of all registration fees, including the hotel reservation fees and the conference registration fee.</p>

<h4>IV. Minor Policy</h4>
<p>
If attendee is under the age of 18 years old, they are required to have an adult chaperone of 21 years or older in order to participate in the conference. Only the 1st chaperone will get free admission to the conference but then any other will have to pay a $20 admission fee.</p>
 
<h4>VII. Photography & Filming</h4>
<p>
For promotional purposes, there will be professional photographers and video production taking place during the conference. If you do not wish to be filmed or photographed, please contact mwrc-sasemail@umn.edu. </p>

<h4>VIII. Admittance</h4>
<p>
In our sole discretion, without refund, we reserve the right to refuse admittance to or expel from the conference anyone that we determine is behaving in a disruptive manner, has possession of any drugs, alcohol, or weapon, or not dressed in appropriate attire.</p>

<h4>IX. Sexual Harassment and Discrimination</h4>
<p>
We do not tolerate any form of sexual harassment or discrimination. If any incident occurs, we reserve the right to expel the offender and confidentially report to the necessary parties if the victim wishes to. </p>


<h4>XI. Disclaimer </h4>
<p>
The organizers may at any time, with or without giving any notice, cancel or postpone the conference, change its venue or any of the other published particulars, or withdraw any invitation to attend. In any case, neither the organizers nor any of their officers, members, or representatives shall be liable for any loss, liability, damage or expense suffered or incurred by any person, nor will they return any money paid to the conference unless they are satisfied not only that the money in question remains under their control but also that the person who paid it has been unfairly prejudiced (as to which, decision shall be in their sole and unfettered discretion and, when announced, final and conclusive). </p>



  
  <h4>XII. Termination</h4>
  
  <p>We may terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
  
  <p>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.</p>
  
  
  <h4>XIII. Governing Law</h4>
  
  <p>These Terms shall be governed and construed in accordance with the laws of Minnesota, United States, without regard to its conflict of law provisions.</p>
  
  <p>Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect. These Terms constitute the entire agreement between us regarding our Service, and supersede and replace any prior agreements we might have between us regarding the Service.</p>
  
  <h4>XIV. Changes</h4>
  
  <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material we will try to provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>
  
  <p>By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Service.</p>
  
  <p>Our Terms of Service agreement was created by TermsFeed.</p>    
  
  <p><strong>Contact Us</strong></p>
  
  <p>If you have any questions about these Terms, please contact the webmaster at nguy1952@umn.edu</p></p>
    </div>
  </div>
  
  
<?php
}



include ROOT."/website/elements/base.php";?>
<script src="https://js.braintreegateway.com/v2/braintree.js"></script>
<script>
$(document).ready(function(){
  // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
  $('.modal-trigger').leanModal();
});
       
var clientToken = "<?php echo BraintreeWrapper::makeToken(); ?>";

/*
braintree.setup(clientToken, "dropin", {
  container: "payment-form",
});*/

  
</script>