<template>
	<DisplayConfirmPassword :lock="lock" @edit-mode="updateDisplay" @step-change="stepChange" v-if="!mdpid"/>

<!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

    <DisplayUpdatePassword @step-change="stepChange" v-if="mdpid==2"/>
	

</template>


<script>

import DisplayConfirmPassword from './DisplayConfirmPassword.vue';
import DisplayUpdatePassword from './DisplayUpdatePassword.vue';

export default {
    props: {
        lock: Boolean
    },

    data() {
        return {
            mdpid: 0,
        }
    },

    emits:["edit-mode"],

    methods: {

        /**
         * Renvoie à l'élement parent un Object explicitant sur l'état des display afin de bloquer ou non  les autres 
         * @param {Boolean} status 
         */
        updateDisplay(status) {
            this.$emit('edit-mode', status);
        },

        /**
         * Récupere la valeuir de newVal afin de modifier le display visible a l'écran
         * @param {number} newVal 
         */
        stepChange(newVal) {
            this.mdpid = newVal;
        }
    },

    components: { DisplayConfirmPassword, DisplayUpdatePassword }
}

</script>