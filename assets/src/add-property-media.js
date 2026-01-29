/**
 * Add Property - Media Upload and Form Management
 * Handles featured image upload, gallery management, child type selection
 */

// Declare wp and propertyTheme variables
const wp = window.wp
const propertyTheme = window.propertyTheme

document.addEventListener("DOMContentLoaded", () => {
  const addPropertyForm = {
    mediaFrame: null,

    init() {
      this.initFeaturedImageUpload()
      this.initGalleryUpload()
      this.initChildTypeSelection()
      this.initGalleryRemoval()
    },

    // Featured Image Upload
    initFeaturedImageUpload() {
      const uploadBtn = document.getElementById("upload-featured-btn")
      if (!uploadBtn) return

      uploadBtn.addEventListener("click", (e) => {
        e.preventDefault()
        this.openMediaFrame("featured-image")
      })
    },

    // Gallery Upload
    initGalleryUpload() {
      const addGalleryBtn = document.getElementById("add-gallery-btn")
      if (!addGalleryBtn) return

      addGalleryBtn.addEventListener("click", (e) => {
        e.preventDefault()
        this.openMediaFrame("gallery", true)
      })
    },

    // Open WordPress Media Library
    openMediaFrame(frameType, multiple = false) {
      if (this.mediaFrame) {
        this.mediaFrame.open()
        return
      }

      const frame = wp.media({
        title: frameType === "featured-image" ? "Select Featured Image" : "Select Gallery Images",
        button: {
          text: frameType === "featured-image" ? "Set Featured Image" : "Add to Gallery",
        },
        multiple: multiple,
        library: {
          type: "image",
        },
      })

      frame.on("select", () => {
        if (frameType === "featured-image") {
          this.handleFeaturedImageSelect(frame)
        } else {
          this.handleGallerySelect(frame)
        }
      })

      this.mediaFrame = frame
      frame.open()
    },

    // Handle Featured Image Selection
    handleFeaturedImageSelect(frame) {
      const selection = frame.state().get("selection").toJSON()
      if (selection.length === 0) return

      const image = selection[0]
      const imageId = image.id
      const imageUrl = image.sizes.medium?.url || image.url

      // Update hidden field
      document.getElementById("featured_image_id").value = imageId

      // Update preview
      const container = document.getElementById("featured-image-container")
      const existingImg = container.querySelector("img")
      if (existingImg) {
        existingImg.src = imageUrl
      } else {
        const img = document.createElement("img")
        img.src = imageUrl
        img.alt = "Featured"
        img.className = "w-full max-h-80 object-cover rounded-lg mb-4"
        const preview = document.createElement("div")
        preview.className = "mb-4"
        preview.appendChild(img)
        container.insertBefore(preview, container.querySelector("button"))
      }
    },

    // Handle Gallery Selection
    handleGallerySelect(frame) {
      const selection = frame.state().get("selection").toJSON()
      if (selection.length === 0) return

      const gallery = document.getElementById("gallery-container")
      const galleryIds = document.getElementById("gallery_ids")

      // Get existing IDs
      const existingIds = galleryIds.value ? galleryIds.value.split(",").map(Number) : []

      selection.forEach((image) => {
        const imageId = image.id

        // Don't add duplicates and limit to 10 images
        if (!existingIds.includes(imageId) && existingIds.length < 10) {
          existingIds.push(imageId)

          const imageUrl = image.sizes.thumbnail?.url || image.url
          const galleryItem = document.createElement("div")
          galleryItem.className = "relative group"
          galleryItem.innerHTML = `
                        <img src="${imageUrl}" alt="Gallery" class="w-full h-24 object-cover rounded-lg">
                        <button type="button" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity remove-gallery-image" data-image-id="${imageId}">×</button>
                    `

          // Add to gallery before the add button
          const addBtn = gallery.querySelector("#add-gallery-btn")
          gallery.insertBefore(galleryItem, addBtn)
        }
      })

      // Update hidden field
      galleryIds.value = existingIds.join(",")
    },

    // Remove Gallery Image
    initGalleryRemoval() {
      const gallery = document.getElementById("gallery-container")
      if (!gallery) return

      gallery.addEventListener("click", (e) => {
        if (e.target.classList.contains("remove-gallery-image")) {
          e.preventDefault()
          const imageId = e.target.dataset.imageId
          const container = e.target.closest("div")

          container.remove()

          // Update hidden field
          const galleryIds = document.getElementById("gallery_ids")
          let ids = galleryIds.value ? galleryIds.value.split(",").map(Number) : []
          ids = ids.filter((id) => id !== Number(imageId))
          galleryIds.value = ids.join(",")
        }
      })
    },

    // Load Child Property Types based on Parent Selection
    initChildTypeSelection() {
      const parentSelect = document.getElementById("property_type_parent")
      const childSelect = document.getElementById("property_type")

      if (!parentSelect || !childSelect) return

      parentSelect.addEventListener("change", async (e) => {
        const parentSlug = e.target.value
        childSelect.innerHTML = '<option value="">Select type first...</option>'

        if (!parentSlug) return

        try {
          const response = await fetch(
            `${propertyTheme.rest_url}property-theme/v1/property-types/children?parent=${parentSlug}`,
            {
              headers: {
                "X-WP-Nonce": propertyTheme.nonce,
              },
            },
          )

          if (!response.ok) throw new Error("Failed to load child types")
          const types = await response.json()

          types.forEach((type) => {
            const option = document.createElement("option")
            option.value = type.slug
            option.textContent = type.name
            childSelect.appendChild(option)
          })
        } catch (error) {
          console.error("[v0] Error loading child types:", error)
          childSelect.innerHTML = '<option value="">Error loading types</option>'
        }
      })
    },
  }

  addPropertyForm.init()
})
