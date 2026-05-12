<!DOCTYPE html>
<html lang="en">
    <head>
      <title>Grace Planes</title>
      <link href="css/planes.css" rel="stylesheet">
      <?php include "header.php"; ?>
    </head>
<body>
 
  <div class="filter-buttons">
    <button class="active" onclick="filterPlanes('all', this)">ALL</button>
    <button class="btn"    onclick="filterPlanes('Airbus', this)">Airbus</button>
    <button class="btn"    onclick="filterPlanes('Boeing', this)">Boeing</button>
    <button class="btn"    onclick="filterPlanes('Gulfstream', this)">Gulfstream</button>
    <button class="btn"    onclick="filterPlanes('Dassault', this)">Dassault</button>
  </div>
 
  <div class="search-button">
    <input type="search" id="search"
      placeholder="Search by manufacturer e.g. Airbus, Boeing..."
      onkeyup="applySearchSortFilter()"
      style="padding:6px; font-size:14px; width:280px;" />
    <button onclick="applySearchSortFilter()">Search</button>
    <select id="sortSelect" onchange="applySearchSortFilter()">
      <option value="default">Sort: Default</option>
      <option value="seats-asc">Seats: Low to High</option>
      <option value="seats-desc">Seats: High to Low</option>
      <option value="manufacturer-asc">Manufacturer: A - Z</option>
      <option value="manufacturer-desc">Manufacturer: Z - A</option>
      <option value="model-asc">Model: A - Z</option>
    </select>
  </div>
 
  <div id="loadingMsg">&#9992; Loading planes from API...</div>
  <div id="noResults">&#9888; No planes found. Try a different search or filter.</div>
 
  <div class="Body">
    <div class="plane" id="planesGrid"></div>
  </div>
 
  <div id="toast"></div>
 
  <script src="planes.js"></script>
 
</body>
</html>