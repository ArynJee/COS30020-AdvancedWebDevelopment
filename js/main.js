// BACK TO TOP
let backToTop = document.querySelector(".back-to-top");
window.addEventListener("scroll", () => {
  if (window.scrollY > 300) {
    backToTop.classList.add("show");
  } else {
    backToTop.classList.remove("show");
  }
});

// REMOVE ALERTS
document.addEventListener('DOMContentLoaded', function(){
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show'); 
            alert.addEventListener('transitionend', () => alert.remove());
        }, 3000); 
    });
});

// PRODUCT PAGE
// filter products based on category
function filterProducts(category){
  document.querySelectorAll(".product-card").forEach(card => {
    if (category === "All" || card.dataset.category === category) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
}

// run when page is ready
document.addEventListener("DOMContentLoaded", () =>{
  const params = new URLSearchParams(window.location.search);
  const selectedCategory = params.get("category") || "All";

  const tabs = document.querySelectorAll("#categoryTabs a");

  // set current tab to active
  tabs.forEach(tab => {
    if (tab.dataset.category === selectedCategory){
      tab.classList.add("active");
    } else {
      tab.classList.remove("active");
    }
  });

  // show products for selected category
  filterProducts(selectedCategory);

  tabs.forEach(tab => {
    tab.addEventListener("click", function (e) {
      e.preventDefault();

      // reset all the tabs
      tabs.forEach(t => t.classList.remove("active"));

      this.classList.add("active");
      filterProducts(this.dataset.category);
    });
  });
});

// PRODUCT PAGINATION
const PRODUCTS_PER_PAGE = 12;

function initTabPagination(tabPane) {
    const cards = Array.from(tabPane.querySelectorAll('.product-card'));
    const container = tabPane.querySelector('.pagination-container');
    if (!container || cards.length === 0) return;

    const totalPages = Math.ceil(cards.length / PRODUCTS_PER_PAGE);
    let currentPage = 1;

    function showPage(page) {
        currentPage = page;
        const start = (page - 1) * PRODUCTS_PER_PAGE;
        const end = start + PRODUCTS_PER_PAGE;
        cards.forEach((card, i) => {
            card.style.display = (i >= start && i < end) ? '' : 'none';
        });
        renderPagination();
    }

    function renderPagination() {
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '<nav aria-label="Product pages"><ul class="pagination">';
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <button class="page-link" data-page="${currentPage - 1}"><i class="bi bi-chevron-left"></i></button></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <button class="page-link" data-page="${i}">${i}</button></li>`;
        }
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <button class="page-link" data-page="${currentPage + 1}"><i class="bi bi-chevron-right"></i></button></li>`;
        html += '</ul></nav>';
        container.innerHTML = html;

        container.querySelectorAll('[data-page]').forEach(btn => {
            btn.addEventListener('click', function () {
                const page = parseInt(this.dataset.page);
                if (page >= 1 && page <= totalPages && page !== currentPage) {
                    showPage(page);
                    const gridTop = tabPane.querySelector('.row').getBoundingClientRect().top + window.scrollY - 80;
                    window.scrollTo({ top: gridTop, behavior: 'smooth' });
                }
            });
        });
    }

    showPage(1);
}

document.addEventListener('DOMContentLoaded', () => {
    const activePane = document.querySelector('#categoryTabsContent .tab-pane.active');
    if (activePane) initTabPagination(activePane);

    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            const target = document.querySelector(e.target.dataset.bsTarget);
            if (target) initTabPagination(target);
        });
    });
});

// image slider
document.querySelectorAll(".image-wrapper").forEach(wrapper => {
  const slider = wrapper.querySelector(".slider");
  if (!slider) return;

  const slides = slider.querySelectorAll(".slide");
  const prevBtn = wrapper.querySelector(".prev");
  const nextBtn = wrapper.querySelector(".next");
  let currentIndex = 0;

  function updateButtons() {
    prevBtn.classList.remove("active");
    nextBtn.classList.remove("active");

    if (currentIndex === 0) {
      nextBtn.classList.add("active");
    } else if (currentIndex === slides.length - 1) {
      prevBtn.classList.add("active");
    } else {
      prevBtn.classList.add("active");
      nextBtn.classList.add("active");
    }
  }

  function showSlide(index) {
    slider.style.transform = `translateX(-${index * 100}%)`;
    updateButtons();
  }

  prevBtn.addEventListener("click", () => {
    if (currentIndex > 0) {
      currentIndex--;
      showSlide(currentIndex);
    }
  });

  nextBtn.addEventListener("click", () => {
    if (currentIndex < slides.length - 1) {
      currentIndex++;
      showSlide(currentIndex);
    }
  });

  showSlide(currentIndex);
});

// LOGIN PAGE
document.addEventListener("DOMContentLoaded", function(){
    const redirectFlag = document.getElementById("redirect-flag");
    if (redirectFlag && redirectFlag.dataset.redirect === "true") {
        setTimeout(function(){
            window.location.href = "main_menu.php";
        }, 1500);
    }

    const showForgot = document.body.dataset.showForgot === "true";
    const showOtp = document.body.dataset.showOtp === "true";
    const showReset = document.body.dataset.showReset === "true";

    if (showForgot) {
        const forgotModalEl = document.getElementById("forgotModal");
        if (forgotModalEl){
            const forgotModal = new bootstrap.Modal(forgotModalEl);
            forgotModal.show();
        }
    }

    if (showOtp) {
        const otpModalEl = document.getElementById("otpModal");
        if (otpModalEl){
            const otpModal = new bootstrap.Modal(otpModalEl);
            otpModal.show();
        }
    }

    if (showReset) {
        const resetModalEl = document.getElementById("resetModal");
        if (resetModalEl){
            const resetModal = new bootstrap.Modal(resetModalEl);
            resetModal.show();
        }
    }

    const forgotModal = document.getElementById('forgotModal');
    if (forgotModal) {
        forgotModal.addEventListener('show.bs.modal', function (){
        });
    }
});

// initialize bootstrap tooltip
document.addEventListener('DOMContentLoaded', function(){
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl, {
        trigger: 'hover'
    }));
});

// initialize on page load
document.addEventListener('DOMContentLoaded', function(){
    const workshopDate = document.getElementById('workshop_date');
    if (workshopDate && workshopDate.value !== ''){
        updateSchedule(workshopDate);
    }
});

// MANAGE WORKSHOP REGISTRATION
// display dates based on workshop title
function updateDateOptions(workshopTitle, workshopData, targetSelectId) {
    const dateSelect = document.getElementById(targetSelectId);
    dateSelect.innerHTML = '<option value="">Select Date/Month</option>';
    
    // find workshop by title
    let workshop = null;
    for (const key in workshopData) {
        if (workshopData[key].title === workshopTitle) {
            workshop = workshopData[key];
            break;
        }
    }
    
    if (!workshop){
        return;
    }
    
    if (workshop.template === 'flexible_dates') {
        Object.keys(workshop.dates).forEach(month => {
            const dates = workshop.dates[month].join(' , ');
            const option = document.createElement('option');
            option.value = month;
            option.textContent = `${month} (${dates})`;
            dateSelect.appendChild(option);
        });
    } else {
        Object.keys(workshop.fixed_sessions).forEach(date => {
            const option = document.createElement('option');
            option.value = date;
            option.textContent = date;
            dateSelect.appendChild(option);
        });
    }
}

// initialize edit workshop registration modal
function initEditWorkshopModal(){
    const editModal = document.getElementById('editWorkshopRegModal');
    if (!editModal) {
        return;
    }
    
    const workshopData = JSON.parse(editModal.getAttribute('data-workshop-data'));
    const currentSelectedMonth = editModal.getAttribute('data-current-selected');
    const workshopSelect = document.getElementById('edit_workshop_title');
    const dateSelect = document.getElementById('edit_selected_month');
    
    // initialize on page load
    if (workshopSelect.value) {
        updateDateOptions(workshopSelect.value, workshopData, 'edit_selected_month');
        
        // set current selected value after populating options
        if (currentSelectedMonth) {
            setTimeout(() => {
                for (let i = 0; i < dateSelect.options.length; i++) {
                    if (dateSelect.options[i].value === currentSelectedMonth) {
                        dateSelect.selectedIndex = i;
                        break;
                    }
                }
            }, 100);
        }
    }
    
    // update dates when workshop changes
    workshopSelect.addEventListener('change', function() {
        updateDateOptions(this.value, workshopData, 'edit_selected_month');
    });
}

// initialize add workshop registration modal
function initAddWorkshopModal(){
    const addModal = document.getElementById('addWorkshopRegModal');
    if (!addModal) return;
    
    const workshopSelect = document.getElementById('add_workshop_title');
    const dateSelect = document.getElementById('add_selected_month');
    
    workshopSelect.addEventListener('change', function() {
        updateDateOptions(this.value, workshopData, 'add_selected_month');
    });
}

// initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initEditWorkshopModal();
    initAddWorkshopModal();
});