<template>
    <div class="limitWidth shadow-sm" :class="classList">
        <slot></slot>
    </div>
</template>

<script>
export default {
    props: {
        className: {
            type: String,
            default: "bg-light"
        },

        wrapperClass: {
            type: String,
            default: "d-flex align-items-center justify-content-center my-3 mx-3 rounded px-2 py-1"
        }
    },

    computed: {
        /**
         * Retourne la liste des classes à appliquer sur l'ensemble du container
         */
        classList() {
            return `${this.className} ${this.wrapperClass}`;
        }
    }
}
</script>

<style lang="scss" scoped>
.limitWidth {   
    position: fixed;
    bottom: 0px;
    z-index: 500;
    box-sizing: border-box;
    right: 0px;
    left:0px;
}
@media (min-width: 576px) {
    .limitWidth {
        left: 52px;
    }
}
@media (min-width :1024px) {
    .limitWidth {
        left: 402px;
    }
}
</style>
    