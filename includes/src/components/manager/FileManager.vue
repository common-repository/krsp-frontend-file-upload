<template>
	<div class="file-manager-wrapper">
		<div class="file-manager">
			<div class="uploaded-file-list">
				<ul class="uploaded-images" v-if="hasImages && !isLoading">
					<uploaded-image show-full="showFull" class="uploaded-image" v-for="image in uploadedImages" :key="image.id + image.name" :image="image"></uploaded-image>
				</ul>
				<div v-else-if="!hasImages && !isLoading" class="no-images">
					{{$t('manager.no_images')}}
				</div>
				<div v-else class="loading-wrapper">
					<svg width='60px' height='60px' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-ripple"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><g> <animate attributeName="opacity" dur="2s" repeatCount="indefinite" begin="0s" keyTimes="0;0.33;1" values="1;1;0"></animate><circle cx="50" cy="50" r="40" stroke="#ffffff" fill="none" stroke-width="5" stroke-linecap="round"><animate attributeName="r" dur="2s" repeatCount="indefinite" begin="0s" keyTimes="0;0.33;1" values="0;22;44"></animate></circle></g><g><animate attributeName="opacity" dur="2s" repeatCount="indefinite" begin="1s" keyTimes="0;0.33;1" values="1;1;0"></animate><circle cx="50" cy="50" r="40" stroke="#c7ccbf" fill="none" stroke-width="5" stroke-linecap="round"><animate attributeName="r" dur="2s" repeatCount="indefinite" begin="1s" keyTimes="0;0.33;1" values="0;22;44"></animate></circle></g></svg>
					<span>{{$t('manager.loading')}}</span>
				</div>
			</div>
			<div class="image-actions clear" v-if="hasImages">
			<button :disabled="isSaving" v-on:click="saveImages" class="button is-full" type="">
				<span class="text-align center">
					<span class="saving-message">{{saveImagesMessage}}</span>
				</span>
			</button>
		</div>
		</div>
	</div>
</template>

<script>
	import i18n from '../../lang/lang'
	import UploadedImage from './image/UploadedImage.vue'
	export default {
		props: ['initUploader'],
		components: {
			UploadedImage,
		},
		name: 'FileManager',
		data() {
			return {
				loadingImages: false
			};
		},
		computed: {
			isLoading() {
				return this.$store.getters.isLoading
			},
			isSaving() {
				return this.$store.getters.isSaving
			},
			hasImages() {
				return this.$store.getters.uploads.length > 0
			},
			uploadedImages() {
				return this.$store.getters.uploads
			},
			saveImagesMessage() {
				return this.isSaving ? this.$t('manager.busy') : this.$t('manager.button_text') // TODO: Update to check saving state
			}
		},
		mounted() {
			this.$store.dispatch('getUploadedImages')
		},
		methods: {
			saveImages() {
				const vm = this
				this.$store.dispatch('saveImages', this.uploadedImages)
					.then((response) => {
						this.$store.dispatch('updateCaptions', response.data.images).then(() => {
							this.$swal({
								type: 'success',
								title: this.$t('manager.success_title'),
								text: this.$t('manager.success_text'),
								confirmButtonText: this.$t('manager.confirm_text'),
							}).then((result) => {
								if (result) {
									this.$store.dispatch('resetUploader')
									window.scrollTo(0, 0)
								}
							})
						})
					})
			}
		}
	};
</script>

<style lang="scss" src="file_manager.scss"></style>