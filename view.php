<!DOCTYPE html>
<head> 
    <title>
        View page
    </title>
      <?php include "header.php" ;?>
    <?php include "api.php";?>
    <link href="css/view.css" rel="stylesheet">
</head>


<body>
  

 <button id="backBtn" onclick="goBack()">&#8592; Back to Planes</button>
 
  <!-- LOADING -->
  <div id="loadingMsg">&#9992; Loading plane details...</div>
 
  <!-- ERROR -->
  <div id="errorMsg">&#9888; Could not load plane details. <a href="planes.html">Go back to planes</a>.</div>
 
  <!-- PLANE DETAIL - shown after loading -->
  <div id="planeDetail">
    <div class="Type">
 
      <!-- IMAGE -->
      <div class="image">
        <img id="planeImg" src="" alt="Plane" class="plane-image-large" width="300px" height="200px"
          onerror="this.src='img/Airbus.png'">
      </div>
 
      <!-- ALL DETAILS -->
      <div class="p">
        <h1 id="planeTitle"></h1>
 
        <table class="info-table">
          <tr><td>Manufacturer</td>   <td id="detManufacturer"></td></tr>
          <tr><td>Model</td>          <td id="detModel"></td></tr>
          <tr><td>Seats</td>          <td id="detSeats"></td></tr>
          <tr><td>Max Range</td>      <td id="detRange"></td></tr>
          <tr><td>Max Speed</td>      <td id="detSpeed"></td></tr>
          <tr><td>Max Cargo</td>      <td id="detCargo"></td></tr>
        </table>
 
        <br>
        <p><b>Description:</b></p>
        <p id="detDescription" style="color:#444; line-height:1.6;"></p>
 
        <br>
        <p><b>Available Cabin Classes:</b></p>
        <ul class="cabin-list" id="detCabins"></ul>
 
        <br>
        <!-- Favourite button -->
        <button id="favBtn" onclick="addToFavourites()">&#9734; Add to Favourites</button>
      </div>
 
    </div>
  </div>
 
  <div id="toast"></div>
 

<script src="view.js"></script>










</body>
</html>