var STUDENT_NUM = "u24911412"; 
    var API_KEY     = "4a79b3cfaafb515774dd0a8f547c6f8e";          
    var API_URL     = "https://wheatley.cs.up.ac.za/api/";
 

   var currentPlane = null;

  
    function goBack() {
      window.location.href = "planes.html";
    }
 
  
    function loadPlaneDetail() {
 
      var planeId   = localStorage.getItem("selectedPlaneId");
      var planeData = localStorage.getItem("selectedPlaneData");
 
      console.log("selectedPlaneId:", planeId);
      console.log("selectedPlaneData:", planeData);
 
      
      if (!planeId) {
        document.getElementById("loadingMsg").style.display = "none";
        showError("No plane selected. Please go back and click a plane image.");
        return;
      }
 
     
      if (planeData) {
        try {
          var plane = JSON.parse(planeData);
          document.getElementById("loadingMsg").style.display = "none";
          currentPlane = plane;
          displayPlane(plane);
          return;
        } catch(e) {
          console.log("Could not parse saved plane data, falling back to API");
        }
      }
 
      document.getElementById("loadingMsg").style.display  = "block";
      document.getElementById("planeDetail").style.display = "none";
 
      var xhr = new XMLHttpRequest();
      xhr.open("POST", API_URL, true);
      xhr.setRequestHeader("Content-Type", "application/json");
 
      xhr.onload = function() {
        document.getElementById("loadingMsg").style.display = "none";
        console.log("API response:", xhr.responseText);
 
        if (xhr.status == 200) {
          var data = JSON.parse(xhr.responseText);
 
          if (data.status == "error") {
            showError("API Error: " + data.data);
            return;
          }
 
          var planes = data.data || [];
          var found  = null;
 
          
          for (var i = 0; i < planes.length; i++) {
            if (String(planes[i].id) == String(planeId)) {
              found = planes[i];
              break;
            }
          }
 
          if (!found) {
           
            showError("Plane not found. Please go back and click a plane again.");
            return;
          }
 
       
          localStorage.setItem("selectedPlaneData", JSON.stringify(found));
 
          currentPlane = found;
          displayPlane(found);
 
        } else {
          showError("Server error " + xhr.status + ". Check your API key.");
        }
      };
 
      xhr.onerror = function() {
        document.getElementById("loadingMsg").style.display = "none";
        showError("Network error - could not reach the API.");
      };
 
      
      xhr.send(JSON.stringify({
        studentnum: STUDENT_NUM,
        apikey:     API_KEY,
        type:       "GetAllPlanes",
        return:     ["id","model","manufacturer","seats","classes",
                     "image_url","description","max_range_km",
                     "max_speed_kmh","max_cargo_kg"]
      }));
    }
 
    
    function displayPlane(p) {
     
      console.log("Displaying plane:", p);
 
      var manufacturer = p.manufacturer  || "Unknown";
      var model        = p.model         || "Unknown Model";
      var seats        = p.seats         || "N/A";
      var classes      = p.classes       || "";
      var description  = p.description   || "No description available.";
      var range        = (p.max_range_km  || p.range     || p.maxRange)  ? (p.max_range_km  || p.range     || p.maxRange)  + " km"   : "N/A";
      var speed        = (p.max_speed_kmh || p.speed     || p.maxSpeed)  ? (p.max_speed_kmh || p.speed     || p.maxSpeed)  + " km/h" : "N/A";
      var cargo        = (p.max_cargo_kg  || p.cargo     || p.maxCargo)  ? (p.max_cargo_kg  || p.cargo     || p.maxCargo)  + " kg"   : "N/A";
      var imgSrc       = p.image_url     || p.imageUrl   || p.img        || "img/Airbus.png";
 
      
      document.title = manufacturer + " " + model + " - Grace Airways";
 
      
      document.getElementById("planeImg").src          = imgSrc;
      document.getElementById("planeImg").alt          = manufacturer + " " + model;
      document.getElementById("planeTitle").innerHTML  = manufacturer + " " + model;
      document.getElementById("detManufacturer").innerHTML = manufacturer;
      document.getElementById("detModel").innerHTML        = model;
      document.getElementById("detSeats").innerHTML        = seats + " passengers";
      document.getElementById("detRange").innerHTML        = range;
      document.getElementById("detSpeed").innerHTML        = speed;
      document.getElementById("detCargo").innerHTML        = cargo;
      document.getElementById("detDescription").innerHTML  = description;
 
      var cabinList = document.getElementById("detCabins");
      cabinList.innerHTML = "";
 
      if (classes) {
        var cabinArr = classes.split(",");
        for (var i = 0; i < cabinArr.length; i++) {
          var label = cabinArr[i].trim();
          if (label != "") {
            var li       = document.createElement("li");
            li.textContent = label;
            cabinList.appendChild(li);
          }
        }
      } else {
        cabinList.innerHTML = "<li>N/A</li>";
      }
 
      var favIds = getFavouriteIds();
      if (favIds.indexOf(String(p.id)) !== -1) {
        document.getElementById("favBtn").innerHTML    = "&#9733; Already in Favourites";
        document.getElementById("favBtn").style.background = "#888";
      }
 
      document.getElementById("planeDetail").style.display = "block";
    }
 
    
    function addToFavourites() {
      if (!currentPlane) return;
 
      var id           = String(currentPlane.id);
      var manufacturer = currentPlane.manufacturer || "Unknown";
      var model        = currentPlane.model        || "Unknown";
      var seats        = currentPlane.seats        || "N/A";
      var imgSrc       = currentPlane.image_url    || "img/Airbus.png";
      var classes      = currentPlane.classes      || "N/A";
 
      var favIds    = getFavouriteIds();
      var favPlanes = getFavouritePlanes();
 
      // Check duplicate
      if (favIds.indexOf(id) !== -1) {
        showToast("&#9733; Already in favourites!", "#f0a500");
        return;
      }
 
      favIds.push(id);
      favPlanes.push({ id: id, manufacturer: manufacturer, model: model,
                       seats: seats, imgSrc: imgSrc, classes: classes });
 
      try {
        localStorage.setItem("graceFavouriteIds",    JSON.stringify(favIds));
        localStorage.setItem("graceFavouritePlanes", JSON.stringify(favPlanes));
 
        document.getElementById("favBtn").innerHTML         = "&#9733; Added to Favourites!";
        document.getElementById("favBtn").style.background  = "#888";
 
        showToast("&#10003; Added: " + manufacturer + " " + model, "green");
 
      } catch(e) {
        showToast("&#9888; Could not save: " + e.message, "red");
      }
    }
 
   
    function getFavouriteIds() {
      try {
        var s = localStorage.getItem("graceFavouriteIds");
        return s ? JSON.parse(s) : [];
      } catch(e) { return []; }
    }
 
    function getFavouritePlanes() {
      try {
        var s = localStorage.getItem("graceFavouritePlanes");
        return s ? JSON.parse(s) : [];
      } catch(e) { return []; }
    }
 
    function showError(msg) {
      document.getElementById("errorMsg").innerHTML  = "&#9888; " + msg + " <a href='planes.html'>&#8592; Go back</a>.";
      document.getElementById("errorMsg").style.display = "block";
    }
 
    function showToast(msg, color) {
      var toast = document.getElementById("toast");
      toast.innerHTML        = msg;
      toast.style.display    = "block";
      toast.style.background = color || "#333";
      setTimeout(function() { toast.style.display = "none"; }, 3000);
    }
 
    window.onload = function() {
      loadPlaneDetail();
    };
 