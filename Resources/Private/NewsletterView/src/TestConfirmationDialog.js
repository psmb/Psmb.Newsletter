import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {SelectBox, Button, Dialog, TextInput} from '@neos-project/react-ui-components';

export default class TestConfirmationDialog extends Component {

    static propTypes = {
        isOpen: PropTypes.bool,
        translate: PropTypes.func.isRequired,
        close: PropTypes.func.isRequired,
        send: PropTypes.func.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            email: ''
        };
    }

    render() {
        const {isOpen, translate, close, send} = this.props;
        return (
            <Dialog
                isOpen={isOpen}
                title={translate('Psmb.Newsletter:Main:js.testConfirmationTitle')}
                onRequestClose={close}
                actions={[
                    <Button onClick={close} style="clean">{translate('Neos.Neos:Main:cancel')}</Button>,
                    <Button disabled={!this.state.email.includes('@')} onClick={() => send(this.state.email)} style="brand">{translate('Psmb.Newsletter:Main:js.send')}</Button>
                ]}
                >
                <div style={{padding: '16px'}}>
                    {translate('Psmb.Newsletter:Main:js.testEmailLabel')}
                    <TextInput
                        onChange={email => this.setState({email})}
                        value={this.state.email}
                        />
                </div>
            </Dialog>
        );
    }
}
