<!DOCTYPE html>
<html lang="">
    <head>
     <link href="css/Styleindex.css" rel="stylesheet">
     <link href="css/Styleplane.css" rel="stylesheet">
     
        <title> Grace flights booking</title>
    </head>


<body>

     <div id="pageLoader">
    <span class="loader-plane">✈</span>
    <div style="font-size:20px; font-weight:bold; letter-spacing:0.1em;">GRACE AIRWAYS</div>
    <div style="font-size:13px; color:#aaa; margin-top:6px;">Preparing your booking experience...</div>
    <div class="loader-bar-wrap">
      <div class="loader-bar" id="loaderBar"></div>
    </div>
    <div class="loader-text" id="loaderText">Loading airports...</div>
  </div>
<?php include "header.php"; ?>


<div class="Grace">
<img src="img/OptionforGrace.png" alt="firstclassG" width="100%" height="20%" usemap="#image">
<map name="image">
    <area shape="rect" coords="0,0,1024,512" href="Firstclass.html" target="_blank">
      <area shape="rect" coords="1024,0,2048,512" href="Economy.html" target="_blank">
        <area shape="rect" coords="0,512,1024,1024" href="Preminum.html" target="_blank">
     <area shape="rect" coords="1024,512,2048,1024" href="Businessclass.html" target="_blank">


</map>

</div>
<p id="apiMessage" style="display:none; text-align:center; font-size:14px; font-weight:bold; padding:10px; margin:10px;"></p>
  
  <form>
    <div class="container">
 
      <div class="name-row">
        <div class="names">
          <label for="fname">Firstname</label>
          <input type="text" id="fname">
        </div>
        <div class="names">
          <label for="lname">Lastname</label>
          <input type="text" id="lname">
        </div>
        <div class="names">
          <label for="email">Email Address</label>
          <input type="email" id="email">
        </div>
      </div>
 
      <div class="input-n">
        <div class="n">
          <label>Pick date and time:</label>
          <input type="datetime-local" id="travelDate">
        </div>
        <div class="n">
          <label>Number of people</label>
          <input type="number" id="noppl" min="1" value="1">
        </div>
 
        <!-- DEPARTURE AIRPORT -->
        <div class="n">
          <label>Departure Airport</label>
          <div class="search-box">
            <input type="text" id="fromSearch" placeholder="Search name, city, country or code..."
              onkeyup="searchAirport('from')" onfocus="searchAirport('from')">
            <div class="dropdown-list" id="fromList"></div>
          </div>
          <input type="hidden" id="fromValue">
          <p id="fromSelected" style="color:green; font-size:13px;"></p>
        </div>
      </div>
 
      <div class="type">
 
        <!-- DESTINATION AIRPORT -->
        <div class="ptype">
          <label>Destination Airport</label>
          <div class="search-box">
            <input type="text" id="toSearch" placeholder="Search name, city, country or code..."
              onkeyup="searchAirport('to')" onfocus="searchAirport('to')">
            <div class="dropdown-list" id="toList"></div>
          </div>
          <input type="hidden" id="toValue">
          <p id="toSelected" style="color:green; font-size:13px;"></p>
        </div>
 
      <!-- Return toggle -->
<div class="ptype">
    <label class="switch">Return flight
        <input type="checkbox" id="returnFlight" onchange="toggleReturnDate()">
        <span class="slider round"></span>
    </label>
</div>

<!-- Return date — hidden until toggle is checked -->
<div class="ptype" id="returnDateDiv" style="display:none;">
    <label>Return Date</label>
    <input type="datetime-local" id="returnDate">
</div>
       
 
        <!-- PLANE SEARCH -->
        <div class="ptype">
          <label>Aircraft</label>
          <div class="search-box">
            <input type="text" id="planeSearch" placeholder="Search aircraft..."
              onkeyup="searchPlane()" onfocus="loadPlanes()">
            <div class="dropdown-list" id="planeList"></div>
          </div>
          <input type="hidden" id="planeValue">
          <p id="planeSelected" style="color:green; font-size:13px;"></p>
        </div>
 
        <!-- CABIN CLASS -->
        <div class="ptype">
          <label>Cabin Class</label>
          <select id="cabinClass" disabled>
            <option value="">-- Select a plane first --</option>
          </select>
          <p id="cabinHint" style="font-size:12px; color:gray;">Pick a plane to see available classes.</p>
        </div>
 
      </div>
 
      <button type="button" onclick="submitBooking()">SUBMIT</button>
      <form onsubmit="return false";>
    </div>
  </form>

<div id="seatCalculator">
    <h2>✈ Plane Seat Calculator</h2>
    <p style="font-size:14px;color:#666;margin-bottom:14px;">
      Find out how many aircraft in our fleet have a certain number of seats.
    </p>
    <div class="calc-row">
      <label>Minimum seats:</label>
      <input type="number" id="minSeats" value="0" min="0">
    </div>
    <div class="calc-row">
      <label>Maximum seats:</label>
      <input type="number" id="maxSeats" value="500" min="0">
    </div>
    <div class="calc-row">
      <label>Filter by manufacturer:</label>
      <select id="calcManufacturer">
        <option value="all">All manufacturers</option>
      </select>
    </div>
    <button id="calcBtn" onclick="calculateSeats()">Calculate</button>
    <div id="calcLoading"><span>&#9992;</span> Fetching plane data...</div>
    <div id="calcResult"></div>
  </div>
 
  <!-- ============================================================
       BONUS 2: ABOUT US + FAQ
  ============================================================ -->
  <div id="aboutSection">
    <h2>About Grace Airways</h2>
    <p>
      Grace Airways is a premium aviation service dedicated to connecting travellers
      across the globe with comfort, safety, and style. Founded with a vision to make
      world-class air travel accessible, we operate a diverse fleet ranging from
      regional turboprops to ultra-long-range private jets.
    </p>
    <p>
      Whether you're flying for business or leisure, our team is committed to delivering
      an exceptional experience from booking to landing.
    </p>
 
    <h2 style="margin-top:30px;">Frequently Asked Questions</h2>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        How do I book a flight?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        Use the booking form above. Search for your departure and destination airports,
        select your aircraft, choose a cabin class, fill in your personal details and
        click Submit. You will receive a confirmation once your booking is processed.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        What cabin classes are available?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        Cabin classes depend on the aircraft you select. Different planes support
        different classes — Economy, Premium Economy, Business, and First Class.
        Once you pick an aircraft, the available classes will automatically appear
        in the Cabin Class dropdown.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        Can I book a return flight?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        Yes! Simply toggle the "Return flight" switch in the booking form before
        submitting. Our team will arrange both legs of your journey.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        How many passengers can I book for?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        You can specify the number of passengers in the "Number of people" field.
        The maximum depends on the seating capacity of your selected aircraft.
        Use our Seat Calculator above to find planes that fit your group size.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        How do I save a plane to my favourites?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        Visit the Planes page, find a plane you like, and click the "Favourite" button.
        Your favourites are saved in your browser and can be viewed on the Favourites page
        at any time — even after closing and reopening the browser.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        What is the Seat Calculator?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        The Seat Calculator (above) lets you find out how many aircraft in our fleet
        have a specific number of seats. You can set a minimum and maximum seat count,
        optionally filter by manufacturer, and instantly see how many planes match.
        This is useful when planning group travel.
      </div>
    </div>
 
    <div class="faq-item">
      <button class="faq-question" onclick="toggleFaq(this)">
        Is my booking data secure?
        <span class="faq-arrow">&#8964;</span>
      </button>
      <div class="faq-answer">
        Yes. Grace Airways uses encrypted connections for all data transmission.
        Your personal and payment information is handled securely and is never
        shared with third parties without your consent.
      </div>
    </div>
 
  </div>






    </body>


<script src="index.js"></script>
</html>
