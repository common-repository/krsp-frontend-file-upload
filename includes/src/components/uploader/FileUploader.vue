<template>
	<div class="krsp-file-upload" id="krsp_uploader">
		<header>
			<div class="file-errors">
				<div class="error" v-show="fileErrors.length > 0" v-for="error in fileErrors" v-bind:key="error.message" v-bind:style="{color: 'red', background: '#fbe3e3', padding: '3px 10px', maxWidth: '200px'}">
					{{error.message}}
					<div class="dismiss" @click="dismissError(error)" v-bind:style="{float: 'right', cursor: 'pointer'}">&times;</div>
				</div>
			</div>
		</header>
		<file-queue v-if="hasImages" progress-color="progressColor"></file-queue>
		<drop-box v-if="!hasImages"></drop-box>
		<file-manager :init-uploader="initUploader"></file-manager>
	</div>
</template>

<script>
	import i18n from '../../lang/lang'
	import Vue from 'vue'
	import axios from 'axios'
	import FileManager from "../manager/FileManager.vue"
	import FileQueue from "../queue/FileQueue.vue"
	import FlashMessage from '../misc/FlashMessage.vue'
	import DropBox from '../dropbox/DropBox.vue'

	export default {
		props: ['ajaxConfig'],
		name: 'FileUploader',
		components: {
			FileManager,
			FileQueue,
			FlashMessage,
			DropBox
		},
		data() {
			return {
				message: '',
				uploader: null,
				uploadFinished: false,
				fileErrors: [],
				options: krsp_file_upload,
			};
		},
		computed: {
			hasFlash() {
				return this.$store.getters.message.text !== ''
			},
			queue() {
				return this.$store.getters.queue
			},
			isBusy() {
				return this.$store.getters.isUploading || this.uploader.state === 2
			},
			dragOrDrop: function() {
				return this.dropStatus == 1;
			},
			hasImages() {
				return this.$store.getters.queue.length > 0
			}
		},
		created(){

		},
		mounted() {
			this.initUploader();
			this.uploader.refresh()
		},
		methods: {
			triggerBrowse(){
			},
			initUploader() {
				const defaultOptions = {
					extensions: "jpg,jpeg,png,gif",
					multi_selection: true,
					multipart: true,
					multiple_queues: true,
					max_file_size: "1mb",
				}

				if(this.options.uploader_options.krsp_file_upload_extensions){
					defaultOptions.extensions = this.options.uploader_options.krsp_file_upload_extensions.replace(/\s+|\s+$/gm,'')
				}

				if(this.options.uploader_options.sizeLimit){
					defaultOptions.max_file_size = `${this.options.uploader_options.sizeLimit}mb`
				}

				this.options = Object.assign(this.options, defaultOptions);
				this.uploader = new plupload.Uploader({
					"runtimes": "html5,silverlight,flash,html4",
					"browse_button": "file_uploader",
					"container": "krsp_uploader",
					"drop_element": "dropbox",
					"file_data_name": "async-upload",
					"multiple_queues": true,
					"multi_selection": true,
					"unique_names": true,
					"max_retries": 1,
					"url": this.options.ajax_url,
					"flash_swf_url": this.options.plugin_dir + 'static/Moxie.swf',
					"silverlight_xap_url": this.options.plugin_dir + "static/Moxie.xap",
					"filters": {
						"mime_types": [{
							"title": "Allowed Files",
							"extensions": this.options.extensions
						}, ],
						"max_file_size": `${this.options.max_file_size}`,
						"prevent__duplicates": true,
					},
					"multipart": true,
					"urlstream_upload": true,
					"multipart_params": {
						"profile": this.options.profile,
						"krspnc": this.options.ajax_nonce,
						"action": "krsp_file_upload"
					},
				});
				this.uploader.init();
				this.uploader.refresh();
				this.uploader.bind('Init', function(up, params) {
					console.debug("Uploader initiated: ", up.features, params);
					if (up.features.dragdrop) {
						up.refresh()
					}
				});
				this.uploader.bind('Browse', this.onBrowse);
				this.uploader.bind('Error', this.onFileError);
				this.uploader.bind('FilesAdded', this.onFilesAdded);
				this.uploader.bind('FilesRemoved', this.onFileRemoved);
				this.uploader.bind('FileUploaded', this.onFileUploaded);
				this.uploader.bind('QueueChanged', this.onQueueChanged);
				this.uploader.bind('UploadComplete', this.onUploadComplete);
				this.uploader.bind('UploadProgress', this.onUploadProgress);
			},
			dismissError(error) {
				this.fileErrors = this.fileErrors.filter(err => err.file.id !== error.file.id)
			},
			onBrowse() {
				this.fileErrors = [];
			},
			onFileError(up, error) {
				console.warn('There has been an error :', error);
				this.fileErrors.push(error);
				this.$swal({
					type: 'error',
					title: "There was an error uploading one or more of your files",
					text: error.message
				})
			},
			onFileRemoved(up, files) {
				console.debug('Removed file', up, files)
			},
			onUploadComplete(up, files) {
				this.uploadFinished = true;
				this.$swal({
					type: 'success',
					title: this.$t('uploaded.success'),
					text: this.$t('uploaded.add_captions'),  // 'You can now add captions to your images (optional)'
					confirmButtonText: this.$t('uploaded.button_confirm'), // Success
				}).then((result) => {
						//console.debug(result);
						this.initUploader()
						this.uploader.refresh()
				})
			},
			onFilesAdded(up, files) {
				let files_limit;
				if(this.options.uploader_options.limitUploads === true){
					files_limit = parseInt(this.options.uploader_options.uploadLimit);
				} else {
					files_limit = 1;
				}

				if (files.length > files_limit) {
					this.$swal({
						type: 'error',
						title:  this.$t('errors.max_limit'),
						text: this.$tc('errors.max_limit_msg', files_limit, {count: files_limit})
					})
					.then(result => {
						this.uploader.stop()
						this.uploader.splice()
					})
					return false
				}
				this.fileErrors = [];
				var vm = this;
				plupload.each(files, function(file) {
					// console.debug('File added', file);
					if (vm.validateFile(file)) {
						vm.$store.dispatch('addImageToQueue', file);
					}
				});
				up.refresh();
				up.start();
			},
			onUploadProgress(up, file) {
			},
			onFileUploaded(up, file, response) {
				var fileData = JSON.parse(response.response);
				console.debug("Image Data: ", fileData);
				if (fileData.success === true) {
					this.$store.dispatch('addUploadedImage', fileData.data.image);
					this.$store.dispatch('updateImage', fileData.data.image)
				}
			},
			onQueueChanged(up) {
				up.refresh();
				this.fileErrors = [];
			},
			startUpload(e) {
				this.uploader.start();
				e.preventDefault();
			},
			uploadImages() {
				this.uploader.start();
			},
			validateFile(file) {
				console.debug("Validate file: ", file);

				var validFiles = ['image/png', 'image/gif', 'image/jpg', 'image/jpeg'];
				if (validFiles.indexOf(file['type']) === -1) {
					this.$store.dispatch('removeImageFromQueue', file)
				} else {
					return true;
				}
				return false;
			},
		}
	};
</script>

<style lang="scss" src="file_uploader.scss"></style>