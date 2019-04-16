using Graham.Static;
using System.ComponentModel.DataAnnotations;

namespace Graham.DataAccess.Model
{
    public class InstagramAccount
    {
        public long Id { get; set; }

        [Required]
        public string Username { get; set; }

        [Required]
        public string Password { get; set; }

        public bool Validated { get; set; } = InstagramAccountValidationValue.NotValidated;

        public bool ValidationInProgress { get; set; } = InstagramAccountValidationProgressValue.ValidationNotInProgress;

    }
}
