<?php
// includes/sidebar.php
?>
<style>
  #sidebar {
    height: 100vh;
    width: 250px;
    position: fixed;
    top: 0;
    left: -250px;
    background-color: #343a40;
    overflow-x: hidden;
    transition: 0.3s;
    padding-top: 60px;
    z-index: 1050;
  }
  #sidebar a {
    padding: 10px 15px;
    text-decoration: none;
    font-size: 18px;
    color: white;
    display: block;
  }
  #sidebar .closebtn {
    position: absolute;
    top: 0;
    right: 15px;
    font-size: 36px;
  }
  #main {
    transition: margin-left .3s;
    margin-left: 0;
  }
  #toggleSidebarBtn {
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 1100;
  }
</style>

<!-- Bouton pour ouvrir la sidebar -->
<button id="toggleSidebarBtn" class="btn btn-primary" onclick="toggleSidebar()">☰</button>

<!-- La sidebar -->
<div id="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">&times;</a>
  <a href="liste_colloques.php">Colloques</a>
  <a href="#">Participants</a>
  <a href="#">Paramètres</a>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("main");
    const isOpen = sidebar.style.left === "0px";
    sidebar.style.left = isOpen ? "-250px" : "0px";
    if (main) {
      main.style.marginLeft = isOpen ? "0" : "250px";
    }
  }
</script>
