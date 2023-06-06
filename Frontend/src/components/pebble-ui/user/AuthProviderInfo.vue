
import { provide } from 'vue';
<template>
    <div class="text-secondary mt-2 my-2 ">
        <div>{{mail}}</div>
        <a href="#!" class="d-flex justify-content-center link-secondary text-decoration-none align-items-center" @click.prevent="infoProvider = !infoProvider">
            <i class="me-2" :class="providerIcon" v-if="providerIcon"></i>
            <span>{{provider}}</span>
        </a>

        <div class="alert-secondary alert mt-2" v-if="infoProvider">
            L'authentification est fournie par votre compte {{provider}}. 
            Votre identifiant et votre mot de passe peuvent être modifiés 
            via ce service.
        </div>
    </div>
</template>

<script>

export default {

    props: {
        mail: String,
        provider: String,
        providerIcon: {
            type: String,
            default: null
        }
    },

    data() {
        return {
            infoProvider: false
        }
    }
    
}

</script>