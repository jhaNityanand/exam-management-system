document.addEventListener('alpine:init', () => {
    Alpine.data('profilePage', (config = {}) => ({
        activeTab: config.initialTab || 'general',
        initials: config.initials || 'U',
        showCropModal: false,
        cropImageSrc: '',
        avatarPreview: null,
        croppedAvatarData: '',
        removeAvatar: false,
        cropper: null,
        avatarError: '',

        tabs: [
            { id: 'general', label: 'General Information', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
            { id: 'address', label: 'Address Details', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
            { id: 'social', label: 'Social Links', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
            { id: 'security', label: 'Security', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
        ],

        get displayAvatarUrl() {
            if (this.avatarPreview) {
                return this.avatarPreview;
            }

            return config.avatarUrl || null;
        },

        get hasPendingAvatar() {
            return Boolean(this.croppedAvatarData);
        },

        get showInitials() {
            return !this.displayAvatarUrl || this.removeAvatar;
        },

        openAvatarPicker() {
            this.avatarError = '';
            this.$refs.avatarInput?.click();
        },

        onAvatarSelected(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            this.avatarError = '';
            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowed.includes(file.type)) {
                this.avatarError = 'Please select a JPG, PNG, GIF, or WebP image.';
                event.target.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.avatarError = 'Image must be smaller than 5MB before cropping.';
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                this.cropImageSrc = loadEvent.target.result;
                this.showCropModal = true;
                this.removeAvatar = false;

                this.$nextTick(() => {
                    this.initCropper();
                });
            };
            reader.onerror = () => {
                this.avatarError = 'Could not read the selected image.';
            };
            reader.readAsDataURL(file);
        },

        initCropper() {
            if (typeof Cropper === 'undefined') {
                this.avatarError = 'Crop tool failed to load. Please refresh the page.';
                this.showCropModal = false;
                return;
            }

            const img = this.$refs.cropImage;
            if (!img) {
                return;
            }

            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }

            img.onload = () => {
                this.cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.9,
                    cropBoxResizable: true,
                    cropBoxMovable: true,
                    background: false,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    minContainerWidth: 280,
                    minContainerHeight: 280,
                });
            };

            img.src = this.cropImageSrc;

            if (img.complete) {
                img.onload();
            }
        },

        rotateCrop(deg) {
            this.cropper?.rotate(deg);
        },

        resetCrop() {
            this.cropper?.reset();
        },

        applyCrop() {
            if (!this.cropper) {
                return;
            }

            const canvas = this.cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                this.avatarError = 'Could not crop the image. Try another photo.';
                return;
            }

            const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
            this.avatarPreview = dataUrl;
            this.croppedAvatarData = dataUrl;
            this.removeAvatar = false;

            if (this.$refs.croppedAvatarInput) {
                this.$refs.croppedAvatarInput.value = dataUrl;
            }

            this.closeCropModal(false);
        },

        cancelCrop() {
            this.closeCropModal(true);
        },

        closeCropModal(resetInput) {
            this.showCropModal = false;
            this.cropImageSrc = '';

            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }

            if (resetInput && this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },

        markAvatarForRemoval() {
            this.avatarPreview = null;
            this.croppedAvatarData = '';
            this.removeAvatar = true;

            if (this.$refs.croppedAvatarInput) {
                this.$refs.croppedAvatarInput.value = '';
            }

            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },

        undoAvatarRemoval() {
            this.removeAvatar = false;
        },
    }));
});
