<template>
	<li class="uploaded-image">
		<modal v-if="showModal" @close="showModal = false">
				<img :src="image.url" width="100%" height="auto" alt="" slot="image" style="float: left" />
		</modal>
		<div class="image-wrapper" >
			<div class="image-container" :class="{'no-image': !image.url}" @click="showModal = true">
				<progressive-img  class="image-src" :src="image.url" :width="image.width" :height="image.height" v-show="image.url" :alt="image.image_name" />
			</div>
			<ul class="image-actions">
				<li><button v-if="!editMode" class="edit-image" @click="editImage"><i class="fas fa-pen-square"></i></button></li>
				<li class="image-caption" @click="editImage">
					<span v-if="image.caption == '' && !isEditing">Set a caption</span>
					<input ref="caption" v-if="isEditing" :disabled="!image.id" type="text" name="image-title" placeholder="Add Image caption" tabindex="image.id" @change="updateImage" @blur="updateImage"  v-model="image.caption">
					<span v-else>{{imageCaption}}</span>
				</li>
				<li><button :disabled="incomplete" class="delete-image" @click="deleteImage(image)"><i class="fas fa-trash" :class="{'deleting': deleting}"></i></button></li>
			</ul>
		</div>
	</li>
</template>

<script>
	import i18n from '../../../lang/lang'
	import moment from 'moment'
	import Modal from '../../misc/modal/Modal.vue'
	export default {
		props: {
			image: {
				type: Object,
				default () {
					return null;
				}
			}
		},
		name: 'UploadedImage',
		components: {
			Modal
		},
		data() {
			return {
				deleting: false,
				isEditing: false,
				showModal: false
			};
		},
		created(){
			window.addEventListener('keyup', this.closeModal)
		},
		computed: {
			imageCaption(){
				return this.image.caption.substring(0, 30) + (this.image.caption.length > 30 ? ' ...' : '')
			},
			editing: {
				get(){
					return this.isEditing == true
				},
				set(value){
					this.isEditing = value
				}
			},
			incomplete() {
				return this.image.percent < 100
			},
			lastModified() {
				return moment(this.image.post_modified).calendar()
			}
		},
		methods: {
			closeModal(e){
				if(e.which == 27 || e.code == 'Escape'){
					this.showModal = false;
				}
			},
			editImage(){
				this.editing = true

				// this.$refs.caption.$el.focus()
				this.$nextTick(() => this.$refs.caption.focus())
			},
			resetImage() {
				this.editing = false;
			},
			updateImage() {
				console.debug("Update image");
					this.image.new_caption = this.image.caption
					this.$store.dispatch('saveImages', [this.image]).then(() => {
						this.$store.dispatch('updateImage', this.image)
					}).catch(err => {
						console.error('Error saving image: ', err.message)
					})
				this.editing = false
			},
			deleteImage(image) {
				this.deleting = true
				this.$swal({
						// dangerMode: true,
						// className: "danger",
						title: this.$t('delete.confirm'),
						text: this.$t('delete.message_warning'),
						type: 'warning',
						showCancelButton: true,
						confirmButtonText: this.$t('delete.confirm_button'),
						confirmButtonColor: "#DD6B55",
						cancelButtonText: this.$t('delete.cancel_button')
					})
					.then(() => {
						this.$store.dispatch('deleteImage', image)
							.then((response) => {})
					}, (dismiss) => {
						// dismiss can be 'overlay', 'cancel', 'close', 'esc', 'timer'
						if (dismiss === 'cancel') {
							this.deleting = false
						}
					})
			},
		}
	};
</script>

<style lang="sass" scoped src="uploaded_image.scss"></style>