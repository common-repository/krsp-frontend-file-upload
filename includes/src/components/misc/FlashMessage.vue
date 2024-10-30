<template>
	<div class="flash-message-wrapper" :class="{success: isSuccess, error: isError}">
		<div class="message alert notification">
			<p>{{message.text}}</p>
		</div>
	</div>
</template>

<script>
	export default {

		name: 'FlashMessage',

		data () {
			return {

			};
		},
		computed: {
			hasMessage(){
				return this.$store.getters.message.text !== ''
			},
			message(){
				return this.$store.getters.message
			},
			isError(){
				return this.$store.getters.message.type === -1
			},
			isSuccess(){
				// console.debug(this.$store.getters.message)
				return this.$store.getters.message.type === 1
			}
		}
	};
</script>

<style lang="scss" scoped>
	.flash-message-wrapper {
		padding: 10px;
		p {
			color: #fff;
		}
		&.success {
			background: rgb(35, 188, 185);
		}
		&.error {
			background: #e52d27;
		}
		margin-bottom: 10px;
	}
</style>