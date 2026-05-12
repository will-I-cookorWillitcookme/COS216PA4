<!DOCTYPE html>
<html>
    <head>
     <title>GRACE Preminum Economy CLASS</title>
       <?php include "header.php" ;?>
    <?php include "api.php";?>
     <link href="css/Preminum.css" rel="stylesheet">
    </head>

    <body>
 



    

<div class="container">
    
<div class="name-row">
<div class="names">
       <label for="fname">Firstname</label>
       <input type="name" id="fname">
</div>


<div class="names">
   <label for="lname">Lastname</label>
    <input type="name" id="lname">
</div>
<div class="names">
  <label for="email">Email Address</label>
  <input type="email" id="email">
</div>


</div>
<div class= "input-n">
  <div class="n">
    <label for="Pick date and date:">Pick date and date:</label>
    <input type="datetime-local" id="Pick date and date:"> 
  </div>
  <div class="n">
    <label for="noofppl">number of people</label>
    <input type="number" id="noppl">
  </div>
  <div class="n">

    <label for="searchlocation" > Location</label>
    <select id="searchlocation" name="searchlocation">
        <option value="Cape Town (CPT) - South Africa">Cape Town (CPT) - South Africa</option>
        <option value="Johannesburg (JNB) - South Africa">Johannesburg (JNB) - South Africa</option>
        <option value="Durban (DUR) - South Africa">Durban (DUR) - South Africa</option>
        <option value="Windhoek (WDH) - Namibia">Windhoek (WDH) - Namibia</option>
        <option value="Harare (HRE) - Zimbabwe">Harare (HRE) - Zimbabwe</option>
        <option value="New York City,New York USA">New York City,New York- USA</option>
        <option value="Luanda (LAD) - Angola">Luanda (LAD) - Angola</option>


    </select>
    
  </div>

  </div>
  <button type="submit">SUBMIT</button>
  </div>


    </body>



</html>