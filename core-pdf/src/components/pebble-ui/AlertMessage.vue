<template>
    <div class="alert" :class="classList" role="alert">
        <i v-if="icon" class="me-3 bi" :class="icon"></i>
        
        <slot></slot>

        <button v-if="dismissible" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</template>

<script>

export default {
    props: {
        variant: {
            type: String,
            default: "info"
        },
        dismissible: {
            type : Boolean,
            default: false
        },
        icon: {
            type: String,
            default: ""
        },
        className: String
    },

    computed: {
        /**
         * Retourne la liste des classes en fonction des propriétés passées
         * 
         * @return {String}
         */
        classList() {
            let classStringList = "";

            if(this.variant) {
                classStringList += ' alert-'+ this.variant;
            }

            if(this.dismissible) {
                classStringList += ' alert-dismissible'
            }

            if (this.icon) {
                classStringList += ' d-flex align-items-center';
            }

            if (this.className) {
                classStringList += ' '+this.className;
            }

            return classStringList;
        }
    },
}

</script>