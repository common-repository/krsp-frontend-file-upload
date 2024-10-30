<template>
	<div class="file-uploader">
		<file-uploader></file-uploader>
	</div>
</template>

<script>
	import FileUploader from './components/uploader/FileUploader.vue'
	import i18n from './lang/lang'
	export default {
		name: 'App',
		components: {
			FileUploader
		},
	};
</script>

<style lang="scss">
	.file-uploader {
		* {
			text-rendering: optimizeLegibility;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}
		header {
			display: block;
			h2 {

			}
		}
		.krsp-file-upload {
			overflow: hidden;
		}
	}
</style>