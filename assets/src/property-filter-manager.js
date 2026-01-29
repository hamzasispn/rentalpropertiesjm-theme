/**
 * Property Filter Manager for Archive Page
 * Handles AJAX filtering with grid/list view toggle
 * Uses actual WordPress REST API endpoints
 */

class PropertyFilterManager {
  constructor(options = {}) {
    this.restUrl = window.wpData?.restUrl || "/wp-json/"
    this.currentFilters = options.initialFilters || {}
    this.viewMode = localStorage.getItem("propertyViewMode") || "grid"
    this.currentPage = 1
    this.perPage = 12
    this.properties = []
    this.totalPages = 1

    this.init()
  }

  init() {
    this.cacheElements()
    this.attachEventListeners()
    this.applyFilters()
  }

  cacheElements() {
    this.filterForm = document.querySelector("[data-filter-form]")
    this.propertiesGrid = document.querySelector("[data-properties-grid]")
    this.propertiesList = document.querySelector("[data-properties-list]")
    this.gridViewBtn = document.querySelector("[data-grid-view-btn]")
    this.listViewBtn = document.querySelector("[data-list-view-btn]")
    this.loadingSpinner = document.querySelector("[data-loading-spinner]")
    this.paginationContainer = document.querySelector("[data-pagination]")
  }

  attachEventListeners() {
    // Filter changes
    if (this.filterForm) {
      this.filterForm.addEventListener("change", (e) => {
        this.currentPage = 1
        this.applyFilters()
      })

      this.filterForm.addEventListener("submit", (e) => {
        e.preventDefault()
        this.currentPage = 1
        this.applyFilters()
      })
    }

    // View mode toggle
    if (this.gridViewBtn) {
      this.gridViewBtn.addEventListener("click", () => {
        this.setViewMode("grid")
      })
    }

    if (this.listViewBtn) {
      this.listViewBtn.addEventListener("click", () => {
        this.setViewMode("list")
      })
    }
  }

  setViewMode(mode) {
    this.viewMode = mode
    localStorage.setItem("propertyViewMode", mode)
    this.renderProperties()
  }

  async applyFilters() {
    this.showLoading(true)

    const params = new URLSearchParams()

    // Collect filter values from form
    const filterInputs = this.filterForm?.querySelectorAll("input, select")
    if (filterInputs) {
      filterInputs.forEach((input) => {
        if (input.name && input.value) {
          const value = input.value?.trim()
          if (value) {
            params.append(input.name, value)
          }
        }
      })
    }

    params.append("page", this.currentPage)
    params.append("per_page", this.perPage)

    try {
      const apiUrl = `${this.restUrl}property-theme/v1/properties/search?${params.toString()}`
      console.log("[v0] Fetching properties from:", apiUrl)

      const response = await fetch(apiUrl)
      const data = await response.json()

      this.properties = data.properties || []
      this.totalPages = data.pages || 1

      console.log("[v0] Received properties:", this.properties.length)
      this.renderProperties()
    } catch (error) {
      console.error("[PropertyFilter] Error:", error)
      this.showError("Failed to load properties. Please try again.")
    } finally {
      this.showLoading(false)
    }
  }

  renderProperties() {
    if (!this.properties.length) {
      this.showEmpty()
      return
    }

    if (this.viewMode === "grid") {
      this.renderGrid()
    } else {
      this.renderList()
    }

    this.renderPagination()
  }

  renderGrid() {
    if (!this.propertiesGrid) return

    this.propertiesGrid.innerHTML = this.properties
      .map(
        (property) => `
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
                <div class="relative h-48 bg-slate-200">
                    <img src="${property.image || "/diverse-property-showcase.png"}" 
                         alt="${this.escapeHtml(property.title)}" 
                         class="w-full h-full object-cover">
                    ${property.featured ? '<div class="absolute top-4 right-4 bg-amber-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Featured</div>' : ""}
                    <div class="absolute top-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">${property.bedrooms} Bed</div>
                    <div class="absolute bottom-4 left-4 px-3 py-1 rounded-full text-sm font-semibold ${property.purpose === "rent" ? "bg-green-600 text-white" : "bg-purple-600 text-white"}">
                        ${property.purpose === "rent" ? "For Rent" : "For Sale"}
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">
                        <a href="${property.permalink}" class="hover:text-blue-600 transition-colors">
                            ${this.escapeHtml(property.title)}
                        </a>
                    </h3>
                    <p class="text-slate-600 text-sm mb-4">${this.escapeHtml(property.address)}</p>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <p class="text-sm text-slate-600">Price</p>
                            <p class="text-2xl font-bold text-blue-600">$${property.price.toLocaleString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-600">${property.area} sqft</p>
                        </div>
                    </div>
                    <div class="flex gap-2 mb-4">
                        <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">${property.bedrooms} Beds</span>
                        <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">${property.bathrooms} Baths</span>
                    </div>
                    <a href="${property.permalink}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-center block">
                        View Details
                    </a>
                </div>
            </div>
        `,
      )
      .join("")
  }

  renderList() {
    if (!this.propertiesGrid) return

    this.propertiesGrid.innerHTML = this.properties
      .map(
        (property) => `
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-48 h-48 md:h-auto bg-slate-200 flex-shrink-0 relative">
                        <img src="${property.image || "/diverse-property-showcase.png"}" 
                             alt="${this.escapeHtml(property.title)}" 
                             class="w-full h-full object-cover">
                        <div class="absolute top-3 left-3 px-2 py-1 rounded-full text-xs font-semibold ${property.purpose === "rent" ? "bg-green-600 text-white" : "bg-purple-600 text-white"}">
                            ${property.purpose === "rent" ? "For Rent" : "For Sale"}
                        </div>
                    </div>
                    <div class="flex-1 p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900 mb-2">
                                <a href="${property.permalink}" class="hover:text-blue-600 transition-colors">
                                    ${this.escapeHtml(property.title)}
                                </a>
                            </h3>
                            <p class="text-slate-600 mb-3">${this.escapeHtml(property.address)}</p>
                            <div class="flex gap-4 mb-4">
                                <span class="text-sm"><strong>${property.bedrooms}</strong> Beds</span>
                                <span class="text-sm"><strong>${property.bathrooms}</strong> Baths</span>
                                <span class="text-sm"><strong>${property.area}</strong> sqft</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-2xl font-bold text-blue-600">$${property.price.toLocaleString()}</p>
                            <a href="${property.permalink}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `,
      )
      .join("")
  }

  renderPagination() {
    if (!this.paginationContainer || this.totalPages <= 1) {
      if (this.paginationContainer) {
        this.paginationContainer.innerHTML = ""
      }
      return
    }

    let html = '<div class="flex justify-center gap-2">'

    for (let i = 1; i <= this.totalPages; i++) {
      html += `
                <button 
                    class="px-3 py-2 rounded ${i === this.currentPage ? "bg-blue-600 text-white" : "bg-slate-200 text-slate-700 hover:bg-slate-300"}"
                    onclick="window.propertyFilterManager.goToPage(${i})"
                >
                    ${i}
                </button>
            `
    }

    html += "</div>"
    this.paginationContainer.innerHTML = html
  }

  goToPage(page) {
    this.currentPage = Math.max(1, Math.min(page, this.totalPages))
    this.applyFilters()
    window.scrollTo({ top: 0, behavior: "smooth" })
  }

  showLoading(show) {
    if (this.loadingSpinner) {
      this.loadingSpinner.style.display = show ? "flex" : "none"
    }
  }

  showEmpty() {
    if (this.propertiesGrid) {
      this.propertiesGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-slate-600 text-lg">No properties found matching your criteria.</p>
                </div>
            `
    }
  }

  showError(message) {
    if (this.propertiesGrid) {
      this.propertiesGrid.innerHTML = `
                <div class="col-span-full text-center py-12 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-red-600">${message}</p>
                </div>
            `
    }
  }

  escapeHtml(text) {
    const div = document.createElement("div")
    div.textContent = text
    return div.innerHTML
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector("[data-filter-form]")) {
    window.propertyFilterManager = new PropertyFilterManager({
      restUrl: window.wpData?.restUrl || "/wp-json/",
      initialFilters: {},
    })
  }
})
