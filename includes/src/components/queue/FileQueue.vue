<template>
	<div class="file-queue-wrapper">
		<div class="file-list" v-if="uploads.length">
			<ul class="upload-temp-images">
					<queued-image v-for="image in uploads" v-bind:key="image.id" :image="image"></queued-image>
			</ul>
		</div>
	</div>
</template>

<script>
import QueuedImage from './image/QueuedImage.vue'
	export default {
		name: 'FileQueue',
		components: {QueuedImage},

		data () {
			return {
			};
		},
		computed: {
			uploads(){
				return this.$store.getters.queue
			},
			hasImages(){
				return this.$store.getters.queue.length > 0
			}
		},
	};
</script>

<style lang="scss" scoped src="file_queue.scss"></style>