//using Graham.DataAccess.Model;
//using Graham.Messages.Commands.Insights;
//using Graham.Messages.Commands.InstagramAccount;
//using Graham.Messages.Commands.Python;
//using Graham.Messages.Events.InstagramAccount;
//using Graham.Services;
//using Graham.Static;
//using NServiceBus;
//using NServiceBus.Logging;
//using System.Threading.Tasks;

//namespace Graham.Backend.Sagas.InstagramAccount
//{
//    public class InstagramAccountValidationSaga : Saga<InstagramAccountValidationSagaData>,
//        IAmStartedByMessages<ValidateInstagramAccount>,
//        IHandleMessages<InstagramAccountLoginSuccessful>,
//        IHandleMessages<InstagramAccountLoginFailed>
//    {
//        private static ILog log = LogManager.GetLogger<InstagramAccountValidationSaga>();

//        protected override void ConfigureHowToFindSaga(SagaPropertyMapper<InstagramAccountValidationSagaData> mapper)
//        {
//            mapper.ConfigureMapping<ValidateInstagramAccount>(message => message.InstagramAccountId).ToSaga(sagaData => sagaData.InstagramAccountId);
//            mapper.ConfigureMapping<InstagramAccountLoginSuccessful>(message => message.InstagramAccountId).ToSaga(sagaData => sagaData.InstagramAccountId);
//            mapper.ConfigureMapping<InstagramAccountLoginFailed>(message => message.InstagramAccountId).ToSaga(sagaData => sagaData.InstagramAccountId);
//        }

//        public async Task Handle(ValidateInstagramAccount message, IMessageHandlerContext context)
//        {
//            log.Info($"Received ValidateInstagramAccount, InstagramAccountId = {message.InstagramAccountId}");

//            await context.Send(new ExecuteLoginScript
//            {
//                InstagramAccountId = message.InstagramAccountId,
//                Username = message.Username,
//                Password = message.Password
//            });
//        }

//        public async Task Handle(InstagramAccountLoginSuccessful message, IMessageHandlerContext context)
//        {
//            log.Info($"Received InstagramAccountLoginSuccessful, InstagramAccountId = {message.InstagramAccountId}");

//            Data.InstagramAccountValidationResult = InstagramAccountValidationValue.Validated;
//            await CompleteSaga(context);
//        }

//        public async Task Handle(InstagramAccountLoginFailed message, IMessageHandlerContext context)
//        {
//            log.Info($"Received InstagramAccountLoginFailed, InstagramAccountId = {message.InstagramAccountId}");

//            Data.InstagramAccountValidationResult = InstagramAccountValidationValue.NotValidated;
//            await CompleteSaga(context);
//        }

//        public async Task CompleteSaga(IMessageHandlerContext context)
//        {
//            await context.Publish(new InstagramAccountValidationComplete
//            {
//                InstagramAccountId = Data.InstagramAccountId,
//                ValidationResult = Data.InstagramAccountValidationResult
//            });

//            log.Info($"InstagramAccountValidationSaga completed, InstagramAccountId = {Data.InstagramAccountId}, " +
//                $"InstagramAccountValidationResult = {Data.InstagramAccountValidationResult}");

//            MarkAsComplete();
//        }
//    }
//}
