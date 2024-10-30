<template>
  <transition name="modal">
    <div class="modal-mask" @click="$emit('close')">
       <button class="close-modal" @click="$emit('close')">
          <i class="far fa-times-circle "></i>
        </button>
      <div class="modal-wrapper">
        
        <div class="modal-container">
         
          <div class="modal-body">
            <slot name="image" ></slot>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
  export default {
    
  }
</script>

<style lang="scss" src="modal.scss" scoped></style>