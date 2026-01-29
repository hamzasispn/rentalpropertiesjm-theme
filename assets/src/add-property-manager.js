;(() => {
  // Media Uploader Configuration
  let mediaFrame = null
  let currentUploadType = null // 'featured' or 'gallery'
  const galleryImageIds = []
  const wp = window.wp // Declare the wp variable

  // Featured Image Upload
  document.addEventListener("DOMContentLoaded", () => {
    // Featured Image Upload Handler
    const uploadFeaturedBtn = document.getElementById("upload-featured-btn")
    if (uploadFeaturedBtn) {
      uploadFeaturedBtn.addEventListener("click", (e) => {
        e.preventDefault()
        currentUploadType = "featured"
        openMediaUploader()
      })
    }

    // Gallery Add Button
    const addGalleryBtn = document.getElementById("add-gallery-btn")
    if (addGalleryBtn) {
      addGalleryBtn.addEventListener("click", (e) => {
        e.preventDefault()
        currentUploadType = "gallery"
        openMediaUploader()
      })
    }

    // Gallery Image Remove
    document.querySelectorAll(".remove-gallery-image").forEach((btn) => {
      btn.addEventListener("click", removeGalleryImage)
    })

    // Property Type Parent/Child Selection
    const typeParentSelect = document.getElementById("property_type_parent")
    if (typeParentSelect) {
      typeParentSelect.addEventListener("change", updateChildTypes)
    }

    // Load existing gallery IDs
    loadExistingGalleryIds()
  })

  // Open WordPress Media Uploader
  function openMediaUploader() {
    if (mediaFrame) {
      mediaFrame.open()
      return
    }

    if (typeof wp === "undefined" || !wp.media) {
      console.error("WordPress Media object not available")
      return
    }

    if (currentUploadType === "featured") {
      mediaFrame = wp.media({
        title: "Select Featured Image",
        button: { text: "Use Image" },
        multiple: false,
        library: { type: "image" },
      })

      mediaFrame.on("select", () => {
        const attachment = mediaFrame.state().get("selection").first().toJSON()
        setFeaturedImage(attachment)
      })
    } else if (currentUploadType === "gallery") {
      mediaFrame = wp.media({
        title: "Select Gallery Images",
        button: { text: "Use Images" },
        multiple: true,
        library: { type: "image" },
      })

      mediaFrame.on("select", () => {
        const attachments = mediaFrame.state().get("selection").toJSON()
        attachments.forEach((attachment) => {
          addGalleryImage(attachment)
        })
      })
    }

    mediaFrame.open()
  }

  // Set Featured Image
  function setFeaturedImage(attachment) {
    const container = document.getElementById("featured-image-container")
    const imageIdInput = document.getElementById("featured_image_id")

    container.innerHTML = `
            <div class="mb-4">
                <img src="${attachment.url}" alt="Featured" class="w-full max-h-80 object-cover rounded-lg">
            </div>
            <input type="hidden" id="featured_image_id" name="featured_image_id" value="${attachment.id}">
            <button type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors" id="upload-featured-btn">
                Change Featured Image
            </button>
        `

    imageIdInput.value = attachment.id

    // Re-attach event listener
    document.getElementById("upload-featured-btn").addEventListener("click", (e) => {
      e.preventDefault()
      currentUploadType = "featured"
      mediaFrame = null
      openMediaUploader()
    })
  }

  // Add Gallery Image
  function addGalleryImage(attachment) {
    if (galleryImageIds.length >= 10) {
      alert("Maximum 10 images allowed")
      return
    }

    if (!galleryImageIds.includes(attachment.id)) {
      galleryImageIds.push(attachment.id)
      renderGallery()
      updateGalleryInput()
    }
  }

  // Remove Gallery Image
  function removeGalleryImage(e) {
    e.preventDefault()
    const imageId = Number.parseInt(this.dataset.imageId)
    galleryImageIds.splice(galleryImageIds.indexOf(imageId), 1)
    renderGallery()
    updateGalleryInput()
  }

  // Render Gallery
  function renderGallery() {
    const container = document.getElementById("gallery-container")
    if (!container) return

    let html = ""

    galleryImageIds.forEach((imageId) => {
      const url = getImageUrl(imageId)
      html += `
                <div class="relative group">
                    <img src="${url}" alt="Gallery" class="w-full h-24 object-cover rounded-lg">
                    <button type="button" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity remove-gallery-image" data-image-id="${imageId}">×</button>
                </div>
            `
    })

    html += `
            <button type="button" id="add-gallery-btn" class="border-2 border-dashed border-slate-300 rounded-lg flex items-center justify-center h-24 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors">
                <span class="text-3xl text-slate-400">+</span>
            </button>
        `

    container.innerHTML = html

    // Re-attach event listeners
    document.getElementById("add-gallery-btn").addEventListener("click", (e) => {
      e.preventDefault()
      currentUploadType = "gallery"
      mediaFrame = null
      openMediaUploader()
    })

    document.querySelectorAll(".remove-gallery-image").forEach((btn) => {
      btn.addEventListener("click", removeGalleryImage)
    })
  }

  // Update Gallery Input Hidden Field
  function updateGalleryInput() {
    document.getElementById("gallery_ids").value = galleryImageIds.join(",")
  }

  // Load Existing Gallery IDs
  function loadExistingGalleryIds() {
    const input = document.getElementById("gallery_ids")
    if (input && input.value) {
      const ids = input.value
        .split(",")
        .map((id) => Number.parseInt(id))
        .filter((id) => id > 0)
      galleryImageIds.length = 0
      galleryImageIds.push(...ids)
    }
  }

  // Get Image URL from Attachment ID
  function getImageUrl(attachmentId) {
    // This would need to be fetched via AJAX in a real implementation
    // For now, we'll create the URL pattern WordPress uses
    return `/wp-json/wp/v2/media/${attachmentId}`
  }

  // Update Child Property Types
  function updateChildTypes(e) {
    const parentSlug = e.target.value
    const childSelect = document.getElementById("property_type")

    if (!parentSlug) {
      childSelect.innerHTML = '<option value="">Select type first...</option>'
      return
    }

    // Get child types via AJAX
    fetch(`/wp-json/property-theme/v1/property-types?parent=${parentSlug}`)
      .then((response) => response.json())
      .then((data) => {
        let html = '<option value="">Choose specific type...</option>'
        if (data.children) {
          data.children.forEach((type) => {
            html += `<option value="${type.slug}">${type.name}</option>`
          })
        }
        childSelect.innerHTML = html
      })
      .catch((error) => console.error("Error loading child types:", error))
  }
})()
