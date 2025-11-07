// // targetting the hamburger links and hamburger icon
// // will either add or remove the open class to the menu and icon

// function toggleMenu() {
//   const menu = document.querySelector(".hamburger-links");
//   const icon = document.querySelector(".hamburger-icon");
  
//   menu.classList.toggle("active");
//   icon.classList.toggle("active");
// }

// // Dynamic GitHub Repository Fetching
// const repoContainer = document.getElementById("repo-container"); 
// const paginationContainer = document.getElementById("pagination");
// const githubUsername = "brickmmo"; 
// const perPage = 9; 
// let currentPage = 1;
// let allRepos = [];
// let filteredRepos = [];
// let currentSearchTerm = '';
// const searchInfo = document.getElementById("search-info");
// const searchResultsText = document.getElementById("search-results-text");

// // Fetch All Repositories with Pagination Support
// async function fetchRepos(page = 1) {
//   try {
//     const response = await fetch(`https://api.github.com/users/${githubUsername}/repos?per_page=200&page=${page}`);
//     const repos = await response.json();

//     if (!Array.isArray(repos)) {
//       console.error("GitHub API response is not an array:", repos);
//       repoContainer.innerHTML = "<p>Failed to load repositories.</p>";
//       return;
//     }

//     allRepos = allRepos.concat(repos);

//     // If 100 repos were fetched, there might be more pages
//     if (repos.length === 100) {
//       // Recursively fetch next pages
//       await fetchRepos(page + 1); 
//     } else {
//       filteredRepos = [...allRepos]; // Initialize filtered repos
//       renderRepos();
//       setupSearch();
//     }
//   } catch (error) {
//     console.error("Error fetching GitHub repositories:", error);
//     repoContainer.innerHTML = "<p>Failed to load repositories.</p>";
//   }
// }

// // Search and Filter Functionality
// function setupSearch() {
//   const searchInput = document.getElementById('search-input');
//   const clearBtn = document.getElementById('clear-search');
//   const filterName = document.getElementById('filter-name');
//   const filterLanguage = document.getElementById('filter-language');
//   const filterDescription = document.getElementById('filter-description');

//   // Search input event listener with debouncing
//   let searchTimeout;
//   searchInput.addEventListener('input', (e) => {
//     clearTimeout(searchTimeout);
//     searchTimeout = setTimeout(() => {
//       performSearch(e.target.value.trim());
//     }, 300);
//   });

//   // Clear search functionality
//   clearBtn.addEventListener('click', () => {
//     searchInput.value = '';
//     performSearch('');
//   });

//   // Filter checkbox listeners
//   [filterName, filterLanguage, filterDescription].forEach(checkbox => {
//     checkbox.addEventListener('change', () => {
//       performSearch(searchInput.value.trim());
//     });
//   });

//   // Show/hide clear button
//   searchInput.addEventListener('input', (e) => {
//     clearBtn.style.display = e.target.value.trim() ? 'block' : 'none';
//   });
// }

// // Perform search based on current filters and search term
// function performSearch(searchTerm) {
//   currentSearchTerm = searchTerm.toLowerCase();
//   currentPage = 1; // Reset to first page
  
//   if (!searchTerm) {
//     filteredRepos = [...allRepos];
//     searchInfo.style.display = 'none';
//   } else {
//     const nameFilter = document.getElementById('filter-name').checked;
//     const languageFilter = document.getElementById('filter-language').checked;
//     const descriptionFilter = document.getElementById('filter-description').checked;
    
//     filteredRepos = allRepos.filter(repo => {
//       let matches = false;
      
//       // Search in repository name
//       if (nameFilter && repo.name.toLowerCase().includes(currentSearchTerm)) {
//         matches = true;
//       }
      
//       // Search in primary language
//       if (languageFilter && repo.language && repo.language.toLowerCase().includes(currentSearchTerm)) {
//         matches = true;
//       }
      
//       // Search in description
//       if (descriptionFilter && repo.description && repo.description.toLowerCase().includes(currentSearchTerm)) {
//         matches = true;
//       }
      
//       return matches;
//     });
    
//     // Show search results info
//     searchInfo.style.display = 'block';
//     searchResultsText.textContent = `Found ${filteredRepos.length} repositories matching "${searchTerm}"`;
//   }
  
//   renderRepos();
// }

// // Render Repositories with Pagination (updated to use filtered repos)
// async function renderRepos() {
//   repoContainer.innerHTML = ""; 
//   const start = (currentPage - 1) * perPage;
//   const end = start + perPage;
//   const reposToShow = filteredRepos.slice(start, end);

//   if (reposToShow.length === 0) {
//     repoContainer.innerHTML = currentSearchTerm ? 
//       "<p>No repositories found matching your search criteria.</p>" : 
//       "<p>No repositories available.</p>";
//     paginationContainer.innerHTML = "";
//     return;
//   }

//   for (const repo of reposToShow) {
//     const languages = await fetchLanguages(repo.languages_url);
//     const repoCard = document.createElement("div");
//     repoCard.classList.add("app-card");

//     // Highlight search terms in the display
//     const highlightedName = highlightSearchTerm(repo.name, currentSearchTerm);
//     const highlightedDescription = highlightSearchTerm(repo.description || "No description available", currentSearchTerm);
//     const highlightedLanguages = highlightSearchTerm(languages || "N/A", currentSearchTerm);

//     repoCard.innerHTML = `
//       <h3 class="card-title">${highlightedName}</h3>
//       <p class="app-description">${highlightedDescription}</p>
//       <p><strong>Languages:</strong> ${highlightedLanguages}</p>
//       <div class="card-buttons-container">
//         <button class="button-app-info" onclick="window.open('${repo.html_url}', '_blank')">GitHub</button>
//         <button class="button-app-github" onclick="window.location.href='repo_details.php?repo=${repo.name}'">View Details</button>
//       </div>
//     `;

//     repoContainer.appendChild(repoCard);
//   }

//   renderPagination();
// }

// // Highlight search terms
// function highlightSearchTerm(text, searchTerm) {
//   if (!searchTerm || !text) return text;
  
//   const regex = new RegExp(`(${searchTerm})`, 'gi');
//   return text.replace(regex, '<mark>$1</mark>');
// }

// // Fetch Languages for Each Repo
// async function fetchLanguages(url) {
//   try {
//     const response = await fetch(url);
//     const data = await response.json();
//     return Object.keys(data).join(", ");
//   } catch (error) {
//     console.error("Error fetching languages:", error);
//     return "N/A";
//   }
// }

// // Pagination Controls with Next/Prev and Page Count (updated to use filtered repos)
// function renderPagination() {
//   paginationContainer.innerHTML = ""; 
//   const totalPages = Math.ceil(filteredRepos.length / perPage);

//   if (totalPages > 1) {
//     // Previous Button
//     if (currentPage > 1) {
//       const prevButton = document.createElement("button");
//       prevButton.innerText = "Previous";
//       prevButton.classList.add("pagination-btn");
//       prevButton.onclick = () => {
//         currentPage--;
//         renderRepos();
//       };
//       paginationContainer.appendChild(prevButton);
//     }

//     // Page Indicator
//     const pageIndicator = document.createElement("span");
//     pageIndicator.classList.add("page-indicator");
//     pageIndicator.innerText = `${currentPage} / ${totalPages}`;
//     paginationContainer.appendChild(pageIndicator);

//     // Next Button
//     if (currentPage < totalPages) {
//       const nextButton = document.createElement("button");
//       nextButton.innerText = "Next";
//       nextButton.classList.add("pagination-btn");
//       nextButton.onclick = () => {
//         currentPage++;
//         renderRepos();
//       };
//       paginationContainer.appendChild(nextButton);
//     }
//   }
// }

// // Load repositories when the page loads
// document.addEventListener("DOMContentLoaded", () => fetchRepos());
