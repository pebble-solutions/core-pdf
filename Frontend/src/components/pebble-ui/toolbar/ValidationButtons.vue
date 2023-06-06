<template>
    <div :class="className" v-if="title">{{title}}</div>
    <div class="py-3">
        <div class="spinner-border" role="status" v-if="pending">
            <span class="visually-hidden">Enregistrement...</span>
        </div>
        <div class="d-flex gap-2" v-else>
            <button class="btn btn-lg btn-success" @click.prevent="confirm()" v-if="successLabel">
                {{successLabel}}
            </button>
            
            <button class="btn btn-lg btn-danger" @click.prevent="cancel()" v-if="cancelLabel">
                {{cancelLabel}}
            </button>
        </div>
    </div>
</template>


<script>
export default {
    props: {
        title: {
            type: String,
            default: ""
        },
        successLabel: {
            type: String,
            default: "Valider"
        },
        cancelLabel: {
            type: String,
            default: "Annuler"
        },
        pending: {
            type: Boolean,
            default: false
        }
    },

    emits: ['confirm', 'cancel'],

    methods: {
        /**
         * Envoie l'événement de validation à l'élément parent
         */
        confirm() {
            this.$emit('confirm');
        },

        /**
         * Envoie l'événement d'annulation à l'élément parent
         */
        cancel() {
            this.$emit('cancel');
        }
    }
}
</script>