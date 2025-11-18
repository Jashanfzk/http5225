<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications | BrickMMO</title>
    <link rel="icon" type="image/x-icon" href="./assets/BrickMMO_Logo_Coloured.png" />
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=arrow_forward" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="./css/style.css">

</head>


<body>

  
  <header>
    
    <nav id="desktop-nav">
      
      <div class="logo">
        <a href="index.php">
          <img src="./assets/BrickMMO_Logo_Coloured.png" alt="brickmmo logo" width="80px">
        </a>
      </div>
      
      <div>
        <ul class="nav-links">
          <li><a href="https://brickmmo.com/">BrickMMo Main Site</a></li>
        </ul>
      </div>

    </nav>

    <section id="hero">
      <h1>BrickMMO Applications</h1>
      <p>A place for all BrickMMO Applications</p>
      
      <div class="search-container">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" id="search-input" placeholder="Search repositories by name or language..." />
          <button id="clear-search" class="clear-btn" style="display: none;">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="search-filters">
          <label>
            <input type="checkbox" id="filter-name" checked> Repository Name
          </label>
          <label>
            <input type="checkbox" id="filter-language" checked> Programming Language
          </label>
          <label>
            <input type="checkbox" id="filter-description"> Description
          </label>
        </div>
      </div>
    </section>

  </header>
  
  <main>

    <section id="applications">
      
      <div id="search-info" style="display: none;">
        <p id="search-results-text"></p>
      </div>
      <div class="applications-container" id="repo-container">
        
      </div>
      
      <div id="pagination"></div>

    </section>
      
  </main>
  
  <footer>
    <div class="footer-container">
      <div class="social-icons">
        <a href="https://www.instagram.com/brickmmo/" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="https://www.youtube.com/channel/UCJJPeP10HxC1qwX_paoHepQ" target="_blank"><i class="fab fa-youtube"></i></a>
        <a href="https://x.com/brickmmo" target="_blank"><i class="fab fa-x"></i></a>
        <a href="https://github.com/BrickMMO" target="_blank"><i class="fab fa-github"></i></a>
        <a href="https://www.tiktok.com/@brickmmo" target="_blank"><i class="fab fa-tiktok"></i></a>
    </div>
      <div id="copyright-container">
        <p id="brickmmo copyright">&copy; BrickMMO. 2025. All rights reserved.</p>
        <p id="lego copyright">LEGO, the LEGO logo and the Minifigure are trademarks of the LEGO Group.</p>
      </div>
    </div>

  </footer>
  
  <script>
  function toggleMenu() {
    const menu = document.querySelector(".hamburger-links");
    const icon = document.querySelector(".hamburger-icon");
    
    menu.classList.toggle("active");
    icon.classList.toggle("active");
  }

  const repoContainer = document.getElementById("repo-container");
  const paginationContainer = document.getElementById("pagination");
  const githubUsername = "brickmmo";
  const perPage = 9; 
  let currentPage = 1;
  let allRepos = [];
  let filteredRepos = [];
  let currentSearchTerm = '';
  const searchInfo = document.getElementById("search-info");
  const searchResultsText = document.getElementById("search-results-text");

  async function fetchRepos(page = 1) {
    try {
      const response = await fetch(`https://api.github.com/users/${githubUsername}/repos?per_page=200&page=${page}`);
      const repos = await response.json();

      if (!Array.isArray(repos)) {
        console.error("GitHub API response is not an array:", repos);
        repoContainer.innerHTML = "<p>Failed to load repositories.</p>";
        return;
      }

      allRepos = allRepos.concat(repos);

      if (repos.length === 100) {
        await fetchRepos(page + 1);
      } else {
        filteredRepos = [...allRepos];
        renderRepos();
        setupSearch();
      }
    } catch (error) {
      console.error("Error fetching GitHub repositories:", error);
      repoContainer.innerHTML = "<p>Failed to load repositories.</p>";
    }
  }

  function setupSearch() {
    const searchInput = document.getElementById('search-input');
    const clearBtn = document.getElementById('clear-search');
    const filterName = document.getElementById('filter-name');
    const filterLanguage = document.getElementById('filter-language');
    const filterDescription = document.getElementById('filter-description');

    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performSearch(e.target.value.trim());
      }, 300);
    });

    clearBtn.addEventListener('click', () => {
      searchInput.value = '';
      performSearch('');
    });

    [filterName, filterLanguage, filterDescription].forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        performSearch(searchInput.value.trim());
      });
    });

    searchInput.addEventListener('input', (e) => {
      clearBtn.style.display = e.target.value.trim() ? 'block' : 'none';
    });
  }

  function performSearch(searchTerm) {
    currentSearchTerm = searchTerm.toLowerCase();
    currentPage = 1;
    
    if (!searchTerm) {
      filteredRepos = [...allRepos];
      searchInfo.style.display = 'none';
    } else {
      const nameFilter = document.getElementById('filter-name').checked;
      const languageFilter = document.getElementById('filter-language').checked;
      const descriptionFilter = document.getElementById('filter-description').checked;
      
      filteredRepos = allRepos.filter(repo => {
        let matches = false;
        
        if (nameFilter && repo.name.toLowerCase().includes(currentSearchTerm)) {
          matches = true;
        }
        
        if (languageFilter && repo.language && repo.language.toLowerCase().includes(currentSearchTerm)) {
          matches = true;
        }
        
        if (descriptionFilter && repo.description && repo.description.toLowerCase().includes(currentSearchTerm)) {
          matches = true;
        }
        
        return matches;
      });
      
      searchInfo.style.display = 'block';
      searchResultsText.textContent = `Found ${filteredRepos.length} repositories matching "${searchTerm}"`;
    }
    
    renderRepos();
  }

  async function renderRepos() {
    repoContainer.innerHTML = "";
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    const reposToShow = filteredRepos.slice(start, end);

    if (reposToShow.length === 0) {
      repoContainer.innerHTML = currentSearchTerm ? 
        "<p>No repositories found matching your search criteria.</p>" : 
        "<p>No repositories available.</p>";
      paginationContainer.innerHTML = "";
      return;
    }

    for (const repo of reposToShow) {
      const languages = await fetchLanguages(repo.languages_url);
      const repoCard = document.createElement("div");
      repoCard.classList.add("app-card");

      const highlightedName = highlightSearchTerm(repo.name, currentSearchTerm);
      const highlightedDescription = highlightSearchTerm(repo.description || "No description available", currentSearchTerm);
      const highlightedLanguages = highlightSearchTerm(languages || "N/A", currentSearchTerm);

      repoCard.innerHTML = `
      <h3 class="card-title">${highlightedName}</h3>
      <p class="app-description">${highlightedDescription}</p>
      <p><strong>Languages:</strong> ${highlightedLanguages}</p>
      <div class="card-buttons-container">
        <button class="button-app-info" onclick="window.open('${repo.html_url}', '_blank')">GitHub</button>
        <button class="button-app-github" onclick="window.location.href='repo_details.php?repo=${repo.name}'">View Details</button>
      </div>
    `;

      repoContainer.appendChild(repoCard);
    }

    renderPagination();
  }

  function highlightSearchTerm(text, searchTerm) {
    if (!searchTerm || !text) return text;
    
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
  }

  async function fetchLanguages(url) {
    try {
      const response = await fetch(url);
      const data = await response.json();
      return Object.keys(data).join(", ");
    } catch (error) {
      console.error("Error fetching languages:", error);
      return "N/A";
    }
  }

  function renderPagination() {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(filteredRepos.length / perPage);

    if (totalPages > 1) {
      if (currentPage > 1) {
        const prevButton = document.createElement("button");
        prevButton.innerText = "Previous";
        prevButton.classList.add("pagination-btn");
        prevButton.onclick = () => {
          currentPage--;
          renderRepos();
        };
        paginationContainer.appendChild(prevButton);
      }

      const pageIndicator = document.createElement("span");
      pageIndicator.classList.add("page-indicator");
      pageIndicator.innerText = `${currentPage} / ${totalPages}`;
      paginationContainer.appendChild(pageIndicator);

      if (currentPage < totalPages) {
        const nextButton = document.createElement("button");
        nextButton.innerText = "Next";
        nextButton.classList.add("pagination-btn");
        nextButton.onclick = () => {
          currentPage++;
          renderRepos();
        };
        paginationContainer.appendChild(nextButton);
      }
    }
  }

  document.addEventListener("DOMContentLoaded", () => fetchRepos());
  </script>

  <script src="https://cdn.brickmmo.com/bar@1.0.0/bar.js"></script>
    
</body>
</html>
