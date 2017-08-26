import React, {PropTypes} from 'react';
import {Button, Dialog} from '@neos-project/react-ui-components';

const ConfirmationDialog = ({isOpen, translate, close, send}) => {
    return (
        <Dialog
            isOpen={isOpen}
            title={translate('Psmb.Newsletter:Main:js.testConfirmationTitle')}
            onRequestClose={close}
            actions={[
                <Button onClick={close} style="clean">{translate('Neos.Neos:Main:cancel')}</Button>,
                <Button onClick={send} style="brand">{translate('Psmb.Newsletter:Main:js.send')}</Button>
            ]}
            >
            <div style={{padding: '16px'}}>{translate('Psmb.Newsletter:Main:js.confirmationDescription')}</div>
        </Dialog>
    );
};
ConfirmationDialog.propTypes = {
    isOpen: PropTypes.bool,
    translate: PropTypes.func.isRequired,
    close: PropTypes.func.isRequired,
    send: PropTypes.func.isRequired
};

export default ConfirmationDialog;
