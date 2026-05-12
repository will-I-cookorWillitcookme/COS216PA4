<!DOCTYPE html>
<html lang="en">
    <head>
      <title>Grace Planes</title>
      <?php include "header.php"; ?>
      <!-- FIX: renamed from favorites.css → favourites.css to match
           the British spelling used consistently across the project -->
      <link href="css/favorites.css" rel="stylesheet">
    </head>
<body>
 
  <div id="favCount"></div>
  <div id="emptyMsg">
    &#9734; You have no favourite planes saved yet.<br><br>
    <a href="planes.php">&#8592; Go to the Planes page to add some!</a>
  </div>
 
  <div class="Body">
    <div class="plane" id="favouritesGrid"></div>
  </div>
 
  <div id="toast"></div>
 
  <script src="favourites.js"></script>
 
</body>
</html>
 