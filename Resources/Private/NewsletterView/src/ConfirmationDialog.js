import React, {PureComponent} from 'react';
import PropTypes from 'prop-types';
import {Button, Dialog} from '@neos-project/react-ui-components';

class ConfirmationDialog extends PureComponent {
    static propTypes = {
        isOpen: PropTypes.bool,
        translate: PropTypes.func.isRequired,
        close: PropTypes.func.isRequired,
        send: PropTypes.func.isRequired,
        subscription: PropTypes.string.isRequired,
        dataSourceAdditionalArguments: PropTypes.object
    };

    state = {
        isLoading: false,
        subscribers: []
    };

    componentDidUpdate(prevProps) {
        if (this.props.subscription !== prevProps.subscription || (prevProps.isOpen === false && this.props.isOpen === true)) {
            this.fetchPreview();
        }
    }

    fetchPreview() {
        if (this.props.subscription && this.props.isOpen) {
            this.setState({isLoading: true, subscribers: []});
            const dataSourceAdditionalArguments = this.props.dataSourceAdditionalArguments;
            const data = new URLSearchParams();
            if (dataSourceAdditionalArguments) {
                Object.keys(dataSourceAdditionalArguments).forEach(option => {
                    data.set('dataSourceAdditionalArguments[' + option + ']', dataSourceAdditionalArguments[option]);
                });
            }
            fetch(`/newsletter/preview?subscription=${this.props.subscription}&${data.toString()}`, {
                credentials: 'include'
            })
                .then(response => response.json())
                .then(subscribers => {
                    this.setState({subscribers, isLoading: false});
                });
        }
    }
    render() {
        const {isOpen, translate, close, send} = this.props;

        const keys = this.state.subscribers[0] ? Object.keys(this.state.subscribers[0]) : [];
        return (
            <Dialog
                isOpen={isOpen}
                title={translate('Psmb.Newsletter:Main:js.confirmationTitle')}
                onRequestClose={close}
                actions={[
                    <Button onClick={close} style="clean">{translate('Neos.Neos:Main:cancel')}</Button>,
                    <Button onClick={send} style="brand">{translate('Psmb.Newsletter:Main:js.send')}</Button>
                ]}
                >
                <div style={{padding: '16px'}}>
                    <div>{translate('Psmb.Newsletter:Main:js.confirmationDescription')}</div>
                    {this.state.isLoading ? translate('Psmb.Newsletter:Main:js.loading') : (
                        <div>
                            <div style={{padding: '16px 0'}}>{translate('Psmb.Newsletter:Main:js.recepients')}: <strong>{this.state.subscribers.length}</strong></div>
                            <table>
                                <tr>{keys.map(key => <th>{key}</th>)}</tr>
                                {this.state.subscribers.map(subscriber => {
                                    return <tr>{keys.map(key => <td>{subscriber[key]}</td>)}</tr>;
                                })}
                            </table>
                        </div>
                    )}
                </div>
            </Dialog>
        );
    }
};

export default ConfirmationDialog;
