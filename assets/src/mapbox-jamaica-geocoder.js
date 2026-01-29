/**
 * Mapbox Geocoder Integration for Jamaica Properties
 * Real-time location search restricted to Jamaica only
 * Uses Mapbox Geocoding API with proximity bias and bounds restriction
 */

const propertyTheme = { mapbox_key: window.wpData?.mapboxKey || "" }
const mapboxgl = window.mapboxgl 
const MapboxGeocoder = window.MapboxGeocoder

class JamaicaMapboxGeocoder {
  constructor(options = {}) {
    this.mapboxToken = options.mapboxToken || propertyTheme.mapbox_key
    this.containerId = options.containerId || "geocoder-container"
    this.inputId = options.inputId || "address-input"
    this.latInputId = options.latInputId || "latitude-input"
    this.lngInputId = options.lngInputId || "longitude-input"
    this.mapId = options.mapId || "property-map"
    this.onSelect = options.onSelect || (() => {})

    // Jamaica boundaries
    this.jamaicaBounds = [
      -78.35, // west
      17.7, // south
      -75.75, // east
      18.55, // north
    ]

    // Jamaica center (for proximity bias)
    this.jamaicaCenter = [-77.0, 18.1]

    this.init()
  }

  init() {
    mapboxgl.accessToken = this.mapboxToken

    // Initialize Mapbox GL
    this.map = new mapboxgl.Map({
      container: this.mapId,
      style: "mapbox://styles/mapbox/streets-v12",
      center: this.jamaicaCenter,
      zoom: 9,
      bounds: this.jamaicaBounds,
    })

    // Initialize Geocoder
    const geocoder = new MapboxGeocoder({
      accessToken: this.mapboxToken,
      marker: true,
      mapboxgl: mapboxgl,
      proximity: this.jamaicaCenter,
      bbox: this.jamaicaBounds,
      countries: "JM", // Jamaica only
      proximity_country: "JM",
    })

    // Mount geocoder to DOM
    const container = document.getElementById(this.containerId)
    if (container) {
      container.appendChild(geocoder.onAdd(this.map))
    }

    // Handle geocoder results
    geocoder.on("result", (event) => {
      const result = event.result
      const coordinates = result.geometry.coordinates

      // Set form inputs
      document.getElementById(this.inputId).value = result.place_name
      document.getElementById(this.lngInputId).value = coordinates[0]
      document.getElementById(this.latInputId).value = coordinates[1]

      // Center map on result
      this.map.flyTo({
        center: coordinates,
        zoom: 12,
        duration: 1000,
      })

      // Add marker
      this.addMarker(coordinates)

      // Custom callback
      this.onSelect({
        address: result.place_name,
        lat: coordinates[1],
        lng: coordinates[0],
        result: result,
      })
    })

    // Handle input clear
    geocoder.on("clear", () => {
      document.getElementById(this.inputId).value = ""
      document.getElementById(this.lngInputId).value = ""
      document.getElementById(this.latInputId).value = ""
      this.removeMarker()
    })

    this.geocoder = geocoder
  }

  addMarker(coordinates) {
    // Remove existing marker
    this.removeMarker()

    const el = document.createElement("div")
    el.id = "property-marker"
    el.className = "w-8 h-8 bg-blue-600 rounded-full border-2 border-white shadow-lg cursor-pointer"

    this.marker = new mapboxgl.Marker(el).setLngLat(coordinates).addTo(this.map)
  }

  removeMarker() {
    if (this.marker) {
      this.marker.remove()
      this.marker = null
    }
  }

  setCoordinates(lat, lng, address = "") {
    if (lat && lng) {
      document.getElementById(this.latInputId).value = lat
      document.getElementById(this.lngInputId).value = lng
      if (address) {
        document.getElementById(this.inputId).value = address
      }
      this.addMarker([lng, lat])
      this.map.flyTo({ center: [lng, lat], zoom: 12 })
    }
  }

  getCoordinates() {
    return {
      address: document.getElementById(this.inputId).value,
      lat: Number.parseFloat(document.getElementById(this.latInputId).value),
      lng: Number.parseFloat(document.getElementById(this.lngInputId).value),
    }
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  // Add property form geocoder
  const addPropertyForm = document.querySelector('form[name="add-property-form"]')
  if (addPropertyForm && document.getElementById("geocoder-container")) {
    window.jamaicaGeocoder = new JamaicaMapboxGeocoder({
      containerId: "geocoder-container",
      inputId: "address-input",
      latInputId: "latitude-input",
      lngInputId: "longitude-input",
      mapId: "property-map",
    })
  }

  // Archive property page geocoder
  const archiveGeocoder = document.getElementById("archive-geocoder-container")
  if (archiveGeocoder) {
    window.archiveGeocoder = new JamaicaMapboxGeocoder({
      containerId: "archive-geocoder-container",
      inputId: "archive-location-input",
      latInputId: "archive-latitude-input",
      lngInputId: "archive-longitude-input",
      mapId: "archive-property-map",
      onSelect: (data) => {
        // Trigger filter update
        if (window.propertyFilterManager) {
          window.propertyFilterManager.applyFilters()
        }
      },
    })
  }
})

// Export for use in other scripts
if (typeof window !== "undefined") {
  window.JamaicaMapboxGeocoder = JamaicaMapboxGeocoder
}
