<template>
    
    <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between" @click.prevent="$emit('select-licence', licence)" :disabled="disabled">
        <div>
            <strong class="d-block">{{licence.computedName}}</strong>
            <span class="d-block font-monospace text-secondary">{{licence._id}}</span>
            <em class="d-block" v-if="licence.description">{{licence.description}}</em>
        </div>
        <i class="spinner-grow spinner-grow-sm" role="status" v-if="pending"></i>
        <i class="bi bi-chevron-right" v-else></i>
    </button>

</template>

<script>

export default {
    props: {
        licence: Object
    },

    data() {
        return {
            disabled: false,
            pending: false
        }
    },

    mounted() {
        this.$app.addEventListener('beforeLicenceChange', licence => {
            this.disabled = true;
            if (licence._id == this.licence._id) {
                this.pending = true;
            }
        });

        this.$app.addEventListener('authChanged', () => {
            this.disabled = false;
            this.pending = false;
        });

        this.$app.addEventListener('authError', () => {
            this.disabled = false;
            this.pending = false;
        });
    }
}

</script>