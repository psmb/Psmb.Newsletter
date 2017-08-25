import React, {PropTypes, Component} from 'react';
import {SelectBox, Button, Dialog, TextInput} from '@neos-project/react-ui-components';
import {connect} from 'react-redux';
import {selectors} from '@neos-project/neos-ui-redux-store';
import {neos} from '@neos-project/neos-ui-decorators';
import {$get} from 'plow-js';
import TestConfirmationDialog from './TestConfirmationDialog';

const fetchSubscriptions = nodeType => fetch(`/newsletter/getSubscriptions?nodeType=${nodeType}`, {
    credentials: 'include'
}).then(response => response.json());

const sendNewsletter = (isTest, email) => {
    const sendEndpointUrl = isTest ? '/newsletter/testSend' : '/newsletter/send';
    const csrfToken = document.getElementById('appContainer').dataset.csrfToken;
    const data = new URLSearchParams();
    data.set('node', this.props.focusedNodeContextPath.replace(/user-.+\;/, 'live;'));
    data.set('subscription', this.state.selectedSubscription);
    data.set('__csrfToken', csrfToken);
    if (isTest && email) {
        data.set('email', email);
    }
    fetch(sendEndpointUrl, {
            credentials: 'include',
            method: 'POST',
            body: data
        })
        .then(response => response.json());
};

@neos(globalRegistry => ({
    i18nRegistry: globalRegistry.get('i18n')
}))
@connect(state => ({
    focusedNodeContextPath: selectors.CR.Nodes.focusedNodePathSelector(state),
    getNodeByContextPath: selectors.CR.Nodes.nodeByContextPath(state)
}))
export default class NewsletterView extends Component {

    static propTypes = {
        focusedNodeContextPath: PropTypes.string,
        getNodeByContextPath: PropTypes.func.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            subscriptions: [],
            selectedSubscription: null,
            confirmationDialogIsOpen: false,
            isError: null,
            isSent: null
        };
        this.selectSubscription = this.selectSubscription.bind(this);
        this.sendTestNewsletter = this.sendTestNewsletter.bind(this);
        this.toggleTestConfirmationDialog = this.toggleTestConfirmationDialog.bind(this);
    }

    componentDidMount() {
        const node = this.props.getNodeByContextPath(this.props.focusedNodeContextPath);
        const nodeType = $get('nodeType', node);
        if (nodeType) {
            fetchSubscriptions(nodeType).then(json => this.setState({subscriptions: json}));
        }
    }

    toggleTestConfirmationDialog(isOpen) {
        this.setState({confirmationDialogIsOpen: isOpen})
    }

    selectSubscription(value) {
        this.setState({selectedSubscription: value});
    }

    sendTestNewsletter(email) {
        const isTest = true;
        sendNewsletter(isTest, email).then(json => json.status === 'success' ? this.setState({isSent: true}) : this.setState({isError: true}));
        this.toggleTestConfirmationDialog(false);
    }

    render() {
        return (
            <div>
                <SelectBox
                    value={this.state.selectedSubscription}
                    options={this.state.subscriptions}
                    onValueChange={this.selectSubscription}
                    />
                <Button style="brand" onClick={() => this.toggleTestConfirmationDialog(true)}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.send')}</Button>
                <Button style="clean" onClick={() => this.toggleTestConfirmationDialog(true)}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.test')}</Button>

                <TestConfirmationDialog
                    isOpen={this.state.confirmationDialogIsOpen}
                    translate={this.props.i18nRegistry.translate.bind(this.props.i18nRegistry)}
                    close={() => toggleTestConfirmationDialog(false)}
                    send={this.sendTestNewsletter}
                    />
            </div>
        );
    }
}
