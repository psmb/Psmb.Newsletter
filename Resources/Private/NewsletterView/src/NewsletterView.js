import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {SelectBox, Button} from '@neos-project/react-ui-components';
import {connect} from 'react-redux';
import {selectors} from '@neos-project/neos-ui-redux-store';
import {neos} from '@neos-project/neos-ui-decorators';
import {$get} from 'plow-js';
import TestConfirmationDialog from './TestConfirmationDialog';
import ConfirmationDialog from './ConfirmationDialog';

const fetchSubscriptions = nodeType => fetch(`/newsletter/getSubscriptions?nodeType=${nodeType}`, {
    credentials: 'include'
}).then(response => response.json());

const sendNewsletter = (focusedNodeContextPath, subscription, isTest, email, dataSourceAdditionalArguments) => {
    let sendEndpointUrl = isTest ? '/newsletter/testSend' : '/newsletter/send';
    const csrfToken = document.getElementById('appContainer').dataset.csrfToken;
    const data = new URLSearchParams();
    data.set('node', focusedNodeContextPath.replace(/user-.+\;/, 'live;'));
    data.set('subscription', subscription);
    data.set('__csrfToken', csrfToken);
    if (isTest && email) {
        data.set('email', email);
    }
    if (dataSourceAdditionalArguments) {
        Object.keys(dataSourceAdditionalArguments).forEach(option => {
            data.set('dataSourceAdditionalArguments[' + pair[0] + ']', dataSourceAdditionalArguments[option]);
        });
    }
    return fetch(sendEndpointUrl, {
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
            testConfirmationDialogIsOpen: false,
            isError: null,
            isSent: null
        };
        this.selectSubscription = this.selectSubscription.bind(this);
        this.sendNewsletter = this.sendNewsletter.bind(this);
        this.sendTestNewsletter = this.sendTestNewsletter.bind(this);
        this.toggleConfirmationDialog = this.toggleConfirmationDialog.bind(this);
        this.toggleTestConfirmationDialog = this.toggleTestConfirmationDialog.bind(this);
    }

    componentDidMount() {
        const node = this.props.getNodeByContextPath(this.props.focusedNodeContextPath);
        const nodeType = $get('nodeType', node);
        if (nodeType) {
            fetchSubscriptions(nodeType).then(json => this.setState({subscriptions: json}));
        }
    }

    toggleConfirmationDialog(isOpen) {
        this.setState({confirmationDialogIsOpen: isOpen})
    }

    toggleTestConfirmationDialog(isOpen) {
        this.setState({testConfirmationDialogIsOpen: isOpen})
    }

    selectSubscription(value) {
        this.setState({selectedSubscription: value});
    }

    sendNewsletter() {
        const isTest = false;
        sendNewsletter(this.props.focusedNodeContextPath, this.state.selectedSubscription, isTest)
            .then(json => {
                return json.status === 'success' ? this.setState({isSent: true}) : this.setState({isError: true});
            })
            .catch(() => this.setState({isError: true}));
        this.toggleConfirmationDialog(false);
    }

    sendTestNewsletter(email) {
        const isTest = true;
        sendNewsletter(this.props.focusedNodeContextPath, this.state.selectedSubscription, isTest, email)
            .then(json => {
                return json.status === 'success' ? this.setState({isSent: true}) : this.setState({isError: true})
            })
            .catch(() => this.setState({isError: true}));
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
                <Button disabled={!this.state.selectedSubscription} style="brand" onClick={() => this.toggleConfirmationDialog(true)}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.send')}</Button>
                <Button disabled={!this.state.selectedSubscription} style="clean" onClick={() => this.toggleTestConfirmationDialog(true)}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.test')}</Button>

                {this.state.isError ? <div style={{marginTop: '16px', color: 'red'}}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.error')}</div> : ''}
                {this.state.isSent ? <div style={{marginTop: '16px', color: 'green'}}>{this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.sent')}</div> : ''}

                <TestConfirmationDialog
                    isOpen={this.state.testConfirmationDialogIsOpen}
                    translate={this.props.i18nRegistry.translate.bind(this.props.i18nRegistry)}
                    close={() => this.toggleTestConfirmationDialog(false)}
                    send={this.sendTestNewsletter}
                    />
                <ConfirmationDialog
                    isOpen={this.state.confirmationDialogIsOpen}
                    translate={this.props.i18nRegistry.translate.bind(this.props.i18nRegistry)}
                    close={() => this.toggleConfirmationDialog(false)}
                    send={this.sendNewsletter}
                    subscription={this.state.selectedSubscription}
                    dataSourceAdditionalArguments={this.props.options && this.props.options.dataSourceAdditionalArguments}
                    />
            </div>
        );
    }
}
